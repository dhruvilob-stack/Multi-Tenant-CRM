<?php

namespace App\Filament\Resources\Shop\Products\Pages;

use App\Filament\Resources\Shop\Products\ProductResource;
use App\Models\User;
use App\Support\UserRole;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addComment')
                ->label('Add Comment')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('primary')
                ->modalHeading('Add Product Comment')
                ->modalDescription('Capture customer feedback for this product.')
                ->form([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255),
                    Select::make('customer_id')
                        ->label('Customer')
                        ->options(
                            User::query()
                                ->where('role', UserRole::CONSUMER)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                        )
                        ->searchable()
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
                    RichEditor::make('content')
                        ->label('Content')
                        ->required()
                        ->columnSpanFull(),
                    Toggle::make('is_visible')
                        ->label('Public visibility')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $this->record->comments()->create([
                        'customer_id' => (int) $data['customer_id'],
                        'rating' => (int) ($data['rating'] ?? 5),
                        'title' => (string) $data['title'],
                        'content' => (string) $data['content'],
                        'is_visible' => (bool) ($data['is_visible'] ?? true),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Comment added')
                        ->body('Product comment was added successfully.')
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
