<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\ProductChangeRequest;
use App\Filament\Support\ResourceDataExchange;
use App\Support\SystemSettings;
use App\Support\UserRole;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withAvg('comments', 'rating'))
            ->columns([
                TextColumn::make('sku')->searchable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('manufacturer.name')->label('Manufacturer'),
                TextColumn::make('category.name')->label('Category'),
                TextColumn::make('base_price')->money(fn (): string => SystemSettings::currencyForCurrentUser())->sortable(),
                TextColumn::make('comments_avg_rating')
                    ->label('Avg Review')
                    ->formatStateUsing(function ($state): string {
                        $avg = round((float) ($state ?? 0), 1);
                        $rounded = max(0, min(5, (int) round($avg)));

                        return str_repeat('★', $rounded)
                            .str_repeat('☆', 5 - $rounded)
                            .' ('.number_format($avg, 1).')';
                    })
                    ->sortable(),
                TextColumn::make('qty')->label('Stock Qty')->sortable(),
                TextColumn::make('purchased_qty')->label('Purchased Qty')->numeric(decimalPlaces: 3)->sortable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('available_for_distributor')->boolean(),
            ])
            ->filters([
                SelectFilter::make('star_rating')
                    ->label('Review Stars')
                    ->options([
                        '5' => '5 Stars',
                        '4' => '4 Stars',
                        '3' => '3 Stars',
                        '2' => '2 Stars',
                        '1' => '1 Star',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $star = (int) ($data['value'] ?? 0);

                        if ($star < 1 || $star > 5) {
                            return $query;
                        }

                        return $query->whereRaw(
                            '(SELECT ROUND(COALESCE(AVG(c.rating), 0)) FROM comments c WHERE c.commentable_type = ? AND c.commentable_id = products.id) = ?',
                            [Product::class, $star]
                        );
                    }),
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
            ->groupedBulkActions([
                DeleteBulkAction::make()
                    ->visible(fn (): bool => in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)),
            ])
            ->toolbarActions([
                ...ResourceDataExchange::toolbarActions('products'),
            ]);
    }
}
