<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Mail;
use App\Support\UserRole;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class EmailTemplates extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.email-templates';

    protected static ?string $slug = 'inbox-mail/templates';

    protected static ?string $cluster = Mail::class;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?string $navigationLabel = 'Templates';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    public ?array $data = [];

    public function mount(): void
    {
        $this->loadTemplates();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Email Templates')
                    ->description('Edit reusable email subject and body templates. These templates can be inserted directly in Compose Mail.')
                    ->schema([
                        Repeater::make('templates')
                            ->label('Template List')
                            ->schema([
                                TextInput::make('key')
                                    ->label('Template Key')
                                    ->placeholder('welcome_mail')
                                    ->required()
                                    ->maxLength(100)
                                    ->regex('/^[a-z0-9_\-]+$/')
                                    ->helperText('Use lowercase letters, numbers, hyphen, or underscore.'),
                                TextInput::make('name')
                                    ->label('Template Name')
                                    ->placeholder('Welcome Mail')
                                    ->required()
                                    ->maxLength(120),
                                TextInput::make('subject')
                                    ->label('Subject')
                                    ->required()
                                    ->maxLength(255),
                                RichEditor::make('body')
                                    ->label('Body')
                                    ->required()
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'underline', 'strike'],
                                        ['h2', 'h3', 'blockquote'],
                                        ['orderedList', 'bulletList'],
                                        ['link', 'redo', 'undo'],
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null) ? (string) $state['name'] : (filled($state['key'] ?? null) ? (string) $state['key'] : 'Template')),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_template')
                ->label('Create Template')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->modalHeading('Create Mail Template')
                ->modalSubmitActionLabel('Create')
                ->schema([
                    TextInput::make('key')
                        ->label('Template Key')
                        ->required()
                        ->placeholder('follow_up')
                        ->maxLength(100)
                        ->regex('/^[a-z0-9_\-]+$/')
                        ->helperText('Unique key used in compose selection.'),
                    TextInput::make('name')
                        ->label('Template Name')
                        ->required()
                        ->placeholder('Follow Up')
                        ->maxLength(120),
                    TextInput::make('subject')
                        ->label('Subject')
                        ->required()
                        ->maxLength(255),
                    RichEditor::make('body')
                        ->label('Body')
                        ->required()
                        ->toolbarButtons([
                            ['bold', 'italic', 'underline', 'strike'],
                            ['h2', 'h3', 'blockquote'],
                            ['orderedList', 'bulletList'],
                            ['link', 'redo', 'undo'],
                        ]),
                ])
                ->action(function (array $data): void {
                    $key = Str::of((string) ($data['key'] ?? ''))
                        ->lower()
                        ->replace(' ', '_')
                        ->replaceMatches('/[^a-z0-9_\-]/', '')
                        ->value();

                    if ($key === '') {
                        Notification::make()->danger()->title('Template key is invalid')->send();

                        return;
                    }

                    $templates = (array) ($this->data['templates'] ?? []);

                    $exists = collect($templates)->contains(fn (array $item): bool => (string) ($item['key'] ?? '') === $key);
                    if ($exists) {
                        Notification::make()->danger()->title('Template key already exists')->send();

                        return;
                    }

                    $templates[] = [
                        'key' => $key,
                        'name' => (string) ($data['name'] ?? ''),
                        'subject' => (string) ($data['subject'] ?? ''),
                        'body' => (string) ($data['body'] ?? ''),
                    ];

                    $this->data['templates'] = $templates;
                    $this->form->fill($this->data);

                    Notification::make()->success()->title('Template added')->send();
                }),
        ];
    }

    public function saveTemplates(): void
    {
        $validated = validator(
            ['data' => $this->data],
            [
                'data.templates' => ['required', 'array', 'min:1'],
                'data.templates.*.key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_\-]+$/'],
                'data.templates.*.name' => ['required', 'string', 'max:120'],
                'data.templates.*.subject' => ['required', 'string', 'max:255'],
                'data.templates.*.body' => ['required', 'string'],
            ]
        )->validate();

        $templates = collect((array) ($validated['data']['templates'] ?? []))
            ->map(function (array $item): array {
                $key = Str::of((string) ($item['key'] ?? ''))
                    ->lower()
                    ->replace(' ', '_')
                    ->replaceMatches('/[^a-z0-9_\-]/', '')
                    ->value();

                return [
                    'key' => $key,
                    'name' => (string) ($item['name'] ?? ''),
                    'subject' => (string) ($item['subject'] ?? ''),
                    'body' => (string) ($item['body'] ?? ''),
                ];
            })
            ->filter(fn (array $item): bool => $item['key'] !== '' && $item['subject'] !== '' && $item['body'] !== '')
            ->values();

        $duplicateKeys = $templates->pluck('key')->duplicates();
        if ($duplicateKeys->isNotEmpty()) {
            Notification::make()->danger()->title('Template keys must be unique')->send();

            return;
        }

        $organization = auth()->user()?->organization;

        if (! $organization) {
            Notification::make()->danger()->title('Organization not found')->send();

            return;
        }

        $store = [];
        foreach ($templates as $template) {
            $store[(string) $template['key']] = [
                'name' => (string) $template['name'],
                'subject' => (string) $template['subject'],
                'body' => (string) $template['body'],
            ];
        }

        $settings = (array) ($organization->settings ?? []);
        $settings['email_templates'] = $store;

        $organization->forceFill(['settings' => $settings])->saveQuietly();

        $this->loadTemplates();

        Notification::make()->success()->title('Email templates saved')->send();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return 'Templates';
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
            UserRole::VENDOR,
            UserRole::CONSUMER,
        ], true);
    }

    private function loadTemplates(): void
    {
        $defaults = $this->defaultTemplates();

        $organization = auth()->user()?->organization;
        $settings = (array) ($organization?->settings ?? []);
        $saved = (array) ($settings['email_templates'] ?? []);

        $merged = array_replace_recursive($defaults, $saved);

        $templates = [];
        foreach ($merged as $key => $template) {
            if (! is_array($template)) {
                continue;
            }

            $templates[] = [
                'key' => (string) $key,
                'name' => (string) ($template['name'] ?? Str::of((string) $key)->replace(['_', '-'], ' ')->title()),
                'subject' => (string) ($template['subject'] ?? ''),
                'body' => (string) ($template['body'] ?? ''),
            ];
        }

        $this->data = ['templates' => $templates];
        $this->form->fill($this->data);
    }

    private function defaultTemplates(): array
    {
        return [
            'invitation' => [
                'name' => 'Invitation',
                'subject' => 'You are invited to join our CRM network',
                'body' => '<p>Hello {{name}}, you have been invited as {{role}}. Click {{accept_url}} to activate your account.</p>',
            ],
            'quotation' => [
                'name' => 'Quotation',
                'subject' => 'Quotation {{quotation_number}} is ready',
                'body' => '<p>Hello {{name}}, quotation {{quotation_number}} totaling {{amount}} is ready for your review.</p>',
            ],
            'invoice' => [
                'name' => 'Invoice',
                'subject' => 'Invoice {{invoice_number}} generated',
                'body' => '<p>Invoice {{invoice_number}} has been generated with due date {{due_date}}.</p>',
            ],
            'payment' => [
                'name' => 'Payment',
                'subject' => 'Payment received for {{invoice_number}}',
                'body' => '<p>Payment of {{amount}} has been received for invoice {{invoice_number}}.</p>',
            ],
        ];
    }
}
