<?php

namespace App\Filament\Support;

use App\Services\CrmDataExchangeService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;

class ResourceDataExchange
{
    public static function importAction(string $resource): Action
    {
        return Action::make('importData')
            ->label('Import')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('warning')
            ->slideOver()
            ->modalWidth('6xl')
            ->schema([
                Section::make('Step 1: Upload Data Containing File')
                    ->schema([
                        Select::make('file_type')
                            ->label('Database / File Type')
                            ->options([
                                'csv' => '.csv',
                                'xlsx' => '.xlsx',
                                'sql' => '.sql',
                                'json' => '.json',
                                'mongoose' => '.mongoose / JSON',
                            ])
                            ->default('csv')
                            ->required(),
                        Select::make('encoding')
                            ->options(['UTF-8' => 'UTF-8'])
                            ->default('UTF-8')
                            ->required(),
                        Select::make('delimiter')
                            ->options([
                                'comma' => 'Comma (,)',
                                'semicolon' => 'Semicolon (;)',
                                'pipe' => 'Pipe (|)',
                                'caret' => 'Caret (^)',
                            ])
                            ->default('comma')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) use ($resource): void {
                                self::syncAutoMapping($resource, $set, $get);
                            })
                            ->required(),
                        TextInput::make('defined_columns')
                            ->label('Define Source Columns (comma separated)')
                            ->placeholder('name,sku,category_name,base_price')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) use ($resource): void {
                                self::syncAutoMapping($resource, $set, $get);
                            })
                            ->columnSpanFull(),
                        FileUpload::make('import_file')
                            ->label('Upload File')
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'text/csv',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/sql',
                                'application/json',
                                'text/plain',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) use ($resource): void {
                                self::syncAutoMapping($resource, $set, $get);
                            })
                            ->columnSpanFull(),
                        TextInput::make('max_file_size_mb')
                            ->label('Max file size (MB)')
                            ->numeric()
                            ->default(100)
                            ->disabled()
                            ->dehydrated(true)
                            ->required()
                            ->helperText('Locked to 100 MB'),
                        Hidden::make('auto_skip_possible')
                            ->default(false)
                            ->dehydrated(false),
                        Placeholder::make('auto_detection_notice')
                            ->label('Auto Detection')
                            ->visible(fn (callable $get): bool => (bool) $get('auto_skip_possible'))
                            ->content('Headers match CRM fields. Steps 2 and 3 are auto-filled and can be skipped.'),
                    ])
                    ->columns(3),
                Section::make('Step 2: Duplicate Record Handling')
                    ->hidden(fn (callable $get): bool => (bool) $get('auto_skip_possible'))
                    ->schema([
                        Select::make('duplicate_handling')
                            ->label('How should duplicates be handled?')
                            ->options([
                                'skip' => 'Skip duplicates',
                                'update' => 'Update existing records',
                                'insert' => 'Insert anyway',
                            ])
                            ->default('skip')
                            ->required(),
                        TextInput::make('matching_fields')
                            ->label('Matching fields (comma separated)')
                            ->placeholder('sku,email,order_number')
                            ->helperText('Optional. Auto-detected when file headers already match CRM fields.'),
                        Toggle::make('skip_invalid_rows')
                            ->default(true)
                            ->label('Skip invalid rows and continue'),
                    ])
                    ->columns(3),
                Section::make('Step 3: Map columns to CRM fields')
                    ->hidden(fn (callable $get): bool => (bool) $get('auto_skip_possible'))
                    ->schema([
                        Repeater::make('column_mappings')
                            ->label('Field Mapping')
                            ->schema([
                                TextInput::make('source_column')
                                    ->label('Source Column')
                                    ->placeholder('category_name'),
                                Select::make('crm_field')
                                    ->label('CRM Field')
                                    ->options(fn() => self::crmFieldOptions($resource))
                                    ->searchable(),
                                TextInput::make('manual_value')
                                    ->label('Manual Value (optional)')
                                    ->placeholder('active'),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Add Mapping')
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (array $data) use ($resource): void {
                $stats = app(CrmDataExchangeService::class)->import($resource, $data);

                $title = $stats['errors'] > 0 ? 'Import completed with warnings' : 'Import completed';
                $body = "Imported: {$stats['imported']}, Updated: {$stats['updated']}, Skipped: {$stats['skipped']}, Errors: {$stats['errors']}";
                if (($stats['auto_skipped_steps'] ?? false) === true) {
                    $body = "Auto mode: uploaded columns matched CRM fields, so step 2 and 3 were skipped.\n" . $body;
                }

                if (!empty($stats['error_samples'])) {
                    $body .= "\n" . implode("\n", $stats['error_samples']);
                }

                $pageUrl = self::currentPageUrl();
                $highlightUrl = self::withHighlight($pageUrl, $stats['first_changed_id'] ?? null);

                Notification::make()
                    ->title($title)
                    ->body($body)
                    ->color($stats['errors'] > 0 ? 'warning' : 'success')
                    ->actions(
                        $highlightUrl
                            ? [Action::make('view')->label('View')->url($highlightUrl)]
                            : []
                    )
                    ->send();

                self::notifyExchangeToOthers(
                    title: 'Data import completed',
                    body: "Resource: {$resource}. {$body}",
                    url: $highlightUrl ?? $pageUrl
                );
            });
    }

    public static function exportAction(string $resource): Action
    {
        return Action::make('exportData')
            ->label('Export')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalWidth('lg')
            ->schema([
                TextInput::make('rows_limit')
                    ->label('How many rows to export?')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50000)
                    ->default(100)
                    ->required(),
                Select::make('file_type')
                    ->label('Export file type')
                    ->options([
                        'csv' => 'CSV',
                        'xlsx' => 'XLSX',
                        'json' => 'JSON',
                        'sql' => 'SQL',
                        'mongoose' => 'Mongoose/JSON',
                    ])
                    ->default('csv')
                    ->required(),
            ])
            ->action(function (array $data) use ($resource) {
                $export = app(CrmDataExchangeService::class)->export($resource, $data);

                Notification::make()
                    ->title('Export completed')
                    ->body("Exported {$resource} successfully as {$export['filename']}.")
                    ->success()
                    ->send();

                self::notifyExchangeToOthers(
                    title: 'Data export completed',
                    body: "Resource: {$resource}. File: {$export['filename']}",
                    url: self::currentPageUrl()
                );

                return response()->download($export['path'], $export['filename'])->deleteFileAfterSend(true);
            });
    }

    /**
     * @return array<int, Action>
     */
    public static function toolbarActions(string $resource): array
    {
        return [
            self::importAction($resource),
            self::exportAction($resource),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function crmFieldOptions(string $resource): array
    {
        $fields = app(CrmDataExchangeService::class)->crmFields($resource);
        $options = [];

        foreach ($fields as $field) {
            $options[$field] = $field;
        }

        return $options;
    }

    protected static function syncAutoMapping(string $resource, callable $set, callable $get): void
    {
        try {
            $analysis = app(CrmDataExchangeService::class)->detectImportHeaders($resource, [
                'file_type' => $get('file_type'),
                'import_file' => $get('import_file'),
                'delimiter' => $get('delimiter'),
                'defined_columns' => $get('defined_columns'),
            ]);
        } catch (\Throwable) {
            $set('auto_skip_possible', false);
            return;
        }

        if (($analysis['headers'] ?? []) !== []) {
            $set('defined_columns', implode(',', $analysis['headers']));
        }

        $set('column_mappings', $analysis['suggested_mappings'] ?? []);
        $set('matching_fields', implode(',', $analysis['matching_fields'] ?? []));
        $set('auto_skip_possible', (bool) ($analysis['auto_skip_possible'] ?? false));

        if ((bool) ($analysis['auto_skip_possible'] ?? false)) {
            $set('duplicate_handling', 'update');
        }
    }

    protected static function withHighlight(string $url, ?int $highlightId): ?string
    {
        if (! $highlightId) {
            return null;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . 'highlight_id=' . $highlightId;
    }

    protected static function currentPageUrl(): string
    {
        $fallback = url()->previous();
        $referer = (string) request()->headers->get('referer', '');
        $candidate = $referer !== '' ? $referer : $fallback;

        if (str_contains($candidate, '/livewire-') && str_contains($candidate, '/update')) {
            $candidate = $fallback;
        }

        if ($candidate === '' || (str_contains($candidate, '/livewire-') && str_contains($candidate, '/update'))) {
            $candidate = url('/admin');
        }

        return $candidate;
    }

    protected static function notifyExchangeToOthers(string $title, string $body, string $url): void
    {
        $actor = auth()->user();
        $recipients = app(CrmDataExchangeService::class)->exchangeRecipients($actor);

        foreach ($recipients as $recipient) {
            $notification = Notification::make()
                ->title($title)
                ->body($body)
                ->success()
                ->actions([
                    Action::make('view')
                        ->label('View')
                        ->url($url),
                ])
                ->sendToDatabase($recipient, isEventDispatched: true);

            $notification->broadcast($recipient);
        }
    }
}
