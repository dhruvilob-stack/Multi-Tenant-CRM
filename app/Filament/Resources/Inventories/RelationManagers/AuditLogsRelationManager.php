<?php

namespace App\Filament\Resources\Inventories\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;

class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

//    protected static ?string $title = 'Audit Logs';
    protected static ?string $title = 'Audit Logs';

    public static function getTitleExtraAttributes(): array
    {
        return [
            'class' => 'w-full text-center block',
        ];
    }
    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('event')->badge(),
                TextEntry::make('performed_by')->placeholder('System'),
                TextEntry::make('performed_role')->placeholder('-'),
                TextEntry::make('ip_address')->label('IP Address')->placeholder('-'),
                TextEntry::make('before')
                    ->label('Before')
                    ->formatStateUsing(function (mixed $state): string {
                        $decoded = is_string($state) ? json_decode($state, true) : $state;

                        if ($decoded === null || $decoded === [] || $decoded === '') {
                            return '```json' . "\n" . '{}' . "\n" . '```';
                        }

                        return '```json' . "\n" . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n" . '```';
                    })
                    ->markdown()
                    ->columnSpanFull(),
                TextEntry::make('after')
                    ->label('After')
                    ->formatStateUsing(function (mixed $state): string {
                        $decoded = is_string($state) ? json_decode($state, true) : $state;

                        if ($decoded === null || $decoded === [] || $decoded === '') {
                            return '```json' . "\n" . '{}' . "\n" . '```';
                        }

                        return '```json' . "\n" . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n" . '```';
                    })
                    ->markdown()
                    ->columnSpanFull(),
                TextEntry::make('created_at')->dateTime(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(new HtmlString('<div class="w-full text-center">Audit Logs</div>'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('event')
                    ->badge()
                    ->sortable(),
                TextColumn::make('performed_by')
                    ->label('By')
                    ->placeholder('System')
                    ->searchable(),
                TextColumn::make('performed_role')
                    ->label('Role')
                    ->placeholder('-'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
