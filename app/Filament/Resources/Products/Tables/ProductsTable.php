<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\ProductChangeRequest;
use App\Support\UserRole;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')->searchable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('manufacturer.name')->label('Manufacturer'),
                TextColumn::make('category.name')->label('Category'),
                TextColumn::make('base_price')->money('USD')->sortable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('available_for_distributor')->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Product $record): bool => ProductResource::canEdit($record)),
                Action::make('request_change')
                    ->label('Request Change')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (): bool => auth()->user()?->role === UserRole::MANUFACTURER)
                    ->form([
                        Textarea::make('requested_changes')
                            ->required()
                            ->label('Change Request Details'),
                    ])
                    ->action(function (Product $record, array $data): void {
                        ProductChangeRequest::query()->create([
                            'product_id' => $record->id,
                            'manufacturer_id' => auth()->id(),
                            'organization_id' => auth()->user()->organization_id,
                            'requested_changes' => $data['requested_changes'],
                            'status' => 'pending',
                        ]);

                        Notification::make()->success()->title('Change request submitted to organization admin')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)),
                ]),
            ]);
    }
}
