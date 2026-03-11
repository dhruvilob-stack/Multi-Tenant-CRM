<?php

namespace App\Filament\Resources\Shop\Products\RelationManagers;

use App\Models\User;
use App\Support\UserRole;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('title')
                    ->required(),

                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name', fn (Builder $query): Builder => $query->where('role', UserRole::CONSUMER))
                    ->searchable()
                    ->preload()
                    ->required(),

                Radio::make('rating')
                    ->label('Rating')
                    ->options([
                        5 => '★',
                        4 => '★',
                        3 => '★',
                        2 => '★',
                        1 => '★',
                    ])
                    ->extraAttributes(['class' => 'fi-rating-stars'])
                    ->inline()
                    ->default(5)
                    ->required(),

                Toggle::make('is_visible')
                    ->label('Public visibility')
                    ->default(true),

                RichEditor::make('content')
                    ->required(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextEntry::make('title')
                    ->placeholder('Untitled'),
                TextEntry::make('customer.name')
                    ->placeholder('No customer'),
                TextEntry::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (?int $state): string => str_repeat('★', max(0, min(5, (int) $state)))
                        .str_repeat('☆', max(0, 5 - max(0, min(5, (int) $state))))),
                IconEntry::make('is_visible')
                    ->label('Public visibility'),
                TextEntry::make('content')
                    ->markdown()
                    ->placeholder('No content'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (?int $state): string => str_repeat('★', max(0, min(5, (int) $state)))
                        .str_repeat('☆', max(0, 5 - max(0, min(5, (int) $state)))))
                    ->sortable(),

                IconColumn::make('is_visible')
                    ->label('Public visibility')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function ($record): void {
                        /** @var User|null $user */
                        $user = auth('tenant')->user();

                        if (! $user) {
                            return;
                        }

                        Notification::make()
                            ->title('New comment')
                            ->icon(Heroicon::ChatBubbleBottomCenterText)
                            ->body(sprintf(
                                '**%s commented on product (%s).**',
                                (string) ($record->customer?->name ?? 'Unknown customer'),
                                (string) ($record->commentable?->name ?? 'Unknown product'),
                            ))
                            ->sendToDatabase($user);
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
