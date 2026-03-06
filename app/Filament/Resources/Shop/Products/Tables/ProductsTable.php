<?php

namespace App\Filament\Resources\Shop\Products\Tables;

use App\Filament\Resources\Shop\Products\ProductResource;
use App\Filament\Support\ResourceDataExchange;
use App\Models\Product;
use App\Support\SystemSettings;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn(Product $record): string => ProductResource::getUrl('view', ['record' => $record]))
            ->columns([
                ImageColumn::make('images')
                    ->label('Image')
                    ->getStateUsing(fn(Product $record): ?string => is_array($record->images) ? ($record->images[0] ?? null) : null)
                    ->disk('local')
                    ->visibility('private')
                    ->square()
                    ->imageSize(44)
                    ->defaultImageUrl(url('/favicon.ico')),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('manufacturer.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_visible')
                    ->label('Visibility')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('price')
                    ->money(fn (): string => SystemSettings::currencyForCurrentUser())
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('qty')
                    ->label('Quantity')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('purchased_qty')
                    ->label('Purchased Qty')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('security_stock')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('published_at')
                    ->label('Publishing date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('name'),
                        TextConstraint::make('slug'),
                        TextConstraint::make('sku')
                            ->label('SKU (Stock Keeping Unit)'),
                        TextConstraint::make('barcode')
                            ->label('Barcode (ISBN, UPC, GTIN, etc.)'),
                        TextConstraint::make('description'),
                        NumberConstraint::make('old_price')
                            ->label('Compare at price')
                            ->icon(Heroicon::CurrencyDollar),
                        NumberConstraint::make('price')
                            ->icon(Heroicon::CurrencyDollar),
                        NumberConstraint::make('cost')
                            ->label('Cost per item')
                            ->icon(Heroicon::CurrencyDollar),
                        NumberConstraint::make('qty')
                            ->label('Quantity'),
                        NumberConstraint::make('security_stock'),
                        BooleanConstraint::make('is_visible')
                            ->label('Visibility'),
                        BooleanConstraint::make('featured'),
                        BooleanConstraint::make('backorder'),
                        BooleanConstraint::make('requires_shipping')
                            ->icon(Heroicon::Truck),
                        DateConstraint::make('published_at')
                            ->label('Publishing date'),
                    ])
                    ->constraintPickerColumns(2),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->deferFilters()
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('adjust_price')
                        ->label('Adjust Price')
                        ->icon(Heroicon::CurrencyDollar)
                        ->color('warning')
                        ->fillForm(fn(Product $record): array => [
                            'price' => (float) $record->price,
                            'old_price' => (float) ($record->old_price ?? $record->price),
                        ])
                        ->schema([
                            TextInput::make('price')
                                ->label('Selling Price')
                                ->numeric()
                                ->minValue(0)
                                ->required(),
                            TextInput::make('old_price')
                                ->label('Compare At Price')
                                ->numeric()
                                ->minValue(0),
                        ])
                        ->action(function (Product $record, array $data): void {
                            $record->update([
                                'price' => (float) $data['price'],
                                'old_price' => array_key_exists('old_price', $data) && filled($data['old_price'])
                                    ? (float) $data['old_price']
                                    : (float) $record->old_price,
                            ]);
                        }),
                    Action::make('adjust_quantity')
                        ->label('Adjust Quantity')
                        ->icon(Heroicon::CubeTransparent)
                        ->color('info')
                        ->fillForm(fn(Product $record): array => [
                            'qty' => (int) $record->qty,
                            'security_stock' => (int) $record->security_stock,
                        ])
                        ->schema([
                            TextInput::make('qty')
                                ->label('Quantity')
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->required(),
                            TextInput::make('security_stock')
                                ->label('Security Stock')
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->required(),
                        ])
                        ->action(function (Product $record, array $data): void {
                            $record->update([
                                'qty' => (int) $data['qty'],
                                'security_stock' => (int) $data['security_stock'],
                            ]);
                        }),
                    Action::make('toggle_visibility')
                        ->icon(fn(Product $record): Heroicon => $record->is_visible ? Heroicon::EyeSlash : Heroicon::Eye)
                        ->label(fn(Product $record): string => $record->is_visible ? 'Hide' : 'Show')
                        ->color('gray')
                        ->action(fn(Product $record) => $record->update(['is_visible' => !$record->is_visible])),
                    DeleteAction::make(),
                ]),
            ])
            ->groupedBulkActions([
                BulkAction::make('toggle_visibility')
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->schema([
                        ToggleButtons::make('is_visible')
                            ->label('Visibility')
                            ->options([
                                '1' => 'Visible',
                                '0' => 'Hidden',
                            ])
                            ->inline()
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        $records->each(fn(Product $record) => $record->update(['is_visible' => (bool) $data['is_visible']]));
                    })
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ])
            ->toolbarActions([
                ...ResourceDataExchange::toolbarActions('products'),
            ]);
    }
}
