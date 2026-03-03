<?php

namespace App\Filament\Resources\Shop\Products\Schemas;

use App\Models\Inventory;
use App\Models\Product;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                Select::make('inventory_reference_id')
                                    ->label('Reference Inventory Record')
                                    ->helperText('Select manually maintained inventory to auto-fill SKU, barcode, quantity, security stock, and inventory price.')
                                    ->options(function (?Product $record): array {
                                        return Inventory::query()
                                            ->where(function ($query) use ($record): void {
                                                $query->whereNull('product_id');

                                                if ($record?->id) {
                                                    $query->orWhere('product_id', $record->id);
                                                }
                                            })
                                            ->with(['product', 'owner'])
                                            ->orderByDesc('id')
                                            ->limit(300)
                                            ->get()
                                            ->mapWithKeys(function (Inventory $inventory): array {
                                                $product = $inventory->product?->name ?? 'Unknown Product';
                                                $owner = $inventory->owner?->name ?? 'Unknown Owner';
                                                $label = sprintf(
                                                    '#%d | %s | SKU: %s | Qty: %s | Price: %0.2f | Owner: %s',
                                                    $inventory->id,
                                                    $product,
                                                    (string) ($inventory->sku ?? '-'),
                                                    (string) $inventory->quantity_available,
                                                    (float) $inventory->unit_price,
                                                    $owner,
                                                );

                                                return [$inventory->id => $label];
                                            })
                                            ->all();
                                    })
                                    ->searchable()
                                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                                    ->afterStateHydrated(function (mixed $state, Set $set, mixed $record): void {
                                        if (filled($state) || ! $record?->id) {
                                            return;
                                        }

                                        $mappedInventoryId = Inventory::query()
                                            ->where('product_id', $record->id)
                                            ->value('id');

                                        if (filled($mappedInventoryId)) {
                                            $set('inventory_reference_id', (int) $mappedInventoryId);
                                        }
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                                        if (! filled($state)) {
                                            return;
                                        }

                                        $inventory = Inventory::query()->find((int) $state);

                                        if (! $inventory) {
                                            return;
                                        }

                                        $set('sku', $inventory->sku);
                                        $set('barcode', $inventory->barcode);
                                        $set('qty', (int) round((float) $inventory->quantity_available));
                                        $set('security_stock', (int) $inventory->security_stock);
                                        $set('price', (float) $inventory->unit_price);
                                        $set('discount_percent', 0);
                                        $set('calculated_price', (float) $inventory->unit_price);
                                    }),

                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                                        $set('slug', Str::slug((string) $state));
                                    }),

                                TextInput::make('slug')
                                    ->readOnly()
                                    ->required()
                                    ->maxLength(255)
                                    ->afterStateHydrated(function (mixed $state, Set $set, mixed $record): void {
                                        if (filled($state)) {
                                            return;
                                        }

                                        $name = (string) ($record?->name ?? '');
                                        if (filled($name)) {
                                            $set('slug', Str::slug($name));
                                        }
                                    })
                                    ->dehydrateStateUsing(function (mixed $state, callable $get): string {
                                        if (filled($state)) {
                                            return (string) $state;
                                        }

                                        return Str::slug((string) $get('name'));
                                    })
                                    ->unique(Product::class, 'slug', ignoreRecord: true)
                                    ->helperText('Auto-generated from product name.'),

                                RichEditor::make('description')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('Images')
                            ->schema([
                                FileUpload::make('images')
                                    ->image()
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->reorderable()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->hiddenLabel(),
                            ])
                            ->collapsible(),

                        Section::make('Pricing')
                            ->schema([
                                TextInput::make('price')
                                    ->label('Inventory price')
                                    ->helperText('Locked from selected inventory record.')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        $basePrice = (float) ($get('price') ?? 0);
                                        $discount = max(0.0, min(100.0, (float) ($get('discount_percent') ?? 0)));
                                        $set('calculated_price', round($basePrice * (1 - ($discount / 100)), 2));
                                    })
                                    ->required(),

                                TextInput::make('discount_percent')
                                    ->label('Discount %')
                                    ->helperText('Applied to inventory price to compute product selling price.')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        $basePrice = (float) ($get('price') ?? 0);
                                        $discount = max(0.0, min(100.0, (float) ($get('discount_percent') ?? 0)));
                                        $set('calculated_price', round($basePrice * (1 - ($discount / 100)), 2));
                                    }),

                                TextInput::make('calculated_price')
                                    ->label('Final product selling price')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->formatStateUsing(function (mixed $state, Get $get): string {
                                        $value = filled($state)
                                            ? (float) $state
                                            : round((float) ($get('price') ?? 0) * (1 - (max(0.0, min(100.0, (float) ($get('discount_percent') ?? 0))) / 100)), 2);

                                        return number_format($value, 2, '.', '');
                                    }),

                                TextInput::make('old_price')
                                    ->label('Compare at price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(99999999.99)
                                    ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
                                    ->readOnly()
                                    ->dehydrated(false),

                                TextInput::make('cost')
                                    ->label('Cost per item')
                                    ->helperText('Customers won\'t see this price.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(99999999.99)
                                    ->rules(['regex:/^\d{1,8}(\.\d{0,2})?$/'])
                                    ->required(),
                            ])
                            ->columns(2),

                        Section::make('Inventory')
                            ->schema([
                                TextInput::make('sku')
                                    ->label('SKU (Stock Keeping Unit)')
                                    ->maxLength(255)
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->helperText('Locked from inventory entry record.')
                                    ->required(),

                                TextInput::make('barcode')
                                    ->label('Barcode (ISBN, UPC, GTIN, etc.)')
                                    ->maxLength(255)
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->helperText('Locked from inventory entry record.'),

                                TextInput::make('qty')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(18446744073709551615)
                                    ->integer()
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->helperText('Locked from inventory quantity available.')
                                    ->required(),

                                TextInput::make('security_stock')
                                    ->helperText('Locked from inventory security stock.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(18446744073709551615)
                                    ->integer()
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->required(),
                            ])
                            ->columns(2),

                        Section::make('Shipping')
                            ->schema([
                                Checkbox::make('backorder')
                                    ->label('This product can be returned'),

                                Checkbox::make('requires_shipping')
                                    ->label('This product will be shipped'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                Toggle::make('is_visible')
                                    ->label('Visibility')
                                    ->helperText('This product will be hidden from all sales channels.')
                                    ->default(true),

                                Toggle::make('featured')
                                    ->label('Featured')
                                    ->default(false),

                                Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'draft' => 'Draft',
                                    ])
                                    ->required()
                                    ->default('draft'),

                                DatePicker::make('published_at')
                                    ->label('Publishing date')
                                    ->default(now())
                                    ->required(),
                            ]),

                        Section::make('Associations')
                            ->schema([
                                Select::make('manufacturer_id')
                                    ->relationship('manufacturer', 'name')
                                    ->searchable()
                                    ->required(),

                                Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->searchable(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
