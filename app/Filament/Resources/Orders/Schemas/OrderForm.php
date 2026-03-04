<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Filament\Resources\Shop\Products\ProductResource as ShopProductResource;
use App\Forms\Components\AddressForm;
use App\Models\Product;
use App\Models\Order;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class OrderForm
{
    private static function calculateLineTotal(float $qty, float $unitPrice, float $discountPercent): float
    {
        $safeQty = max($qty, 0);
        $safeUnitPrice = max($unitPrice, 0);
        $safeDiscount = max(0, min(100, $discountPercent));
        $netUnitPrice = $safeUnitPrice * (1 - ($safeDiscount / 100));

        return round($safeQty * $netUnitPrice, 2);
    }

    private static function recalculateLineTotal(callable $set, callable $get): void
    {
        $qty = (float) ($get('qty') ?: 0);
        $unitPrice = (float) ($get('unit_price') ?: 0);
        $discountPercent = (float) ($get('discount_percent') ?: 0);

        $set('line_total', static::calculateLineTotal($qty, $unitPrice, $discountPercent));
    }

    private static function normalizeItemData(array $item): array
    {
        $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;
        $product = $productId > 0
            ? Product::query()->find($productId, ['id', 'name', 'base_price'])
            : null;

        $qty = max((float) ($item['qty'] ?? 1), 1);
        $unitPrice = (float) ($product?->base_price ?? ($item['unit_price'] ?? 0));
        $discountPercent = max(0, min(100, (float) ($item['discount_percent'] ?? 0)));

        $item['product_id'] = $productId ?: null;
        $item['item_name'] = (string) ($product?->name ?? ($item['item_name'] ?? ''));
        $item['qty'] = $qty;
        $item['unit_price'] = $unitPrice;
        $item['discount_percent'] = $discountPercent;
        $item['line_total'] = static::calculateLineTotal($qty, $unitPrice, $discountPercent);

        return $item;
    }

    public static function normalizeItemsAndTotals(array $data): array
    {
        if (! array_key_exists('items', $data)) {
            return $data;
        }

        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $productIds = collect($items)
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $productsById = Product::query()
            ->whereIn('id', $productIds)
            ->get(['id', 'name', 'base_price'])
            ->keyBy('id');

        $normalizedItems = collect($items)->map(function (array $item) use ($productsById): array {
            $productId = isset($item['product_id']) ? (int) $item['product_id'] : null;
            $product = $productId ? $productsById->get($productId) : null;

            $qty = max((float) ($item['qty'] ?? 1), 1);
            $unitPrice = (float) ($product?->base_price ?? ($item['unit_price'] ?? 0));
            $discountPercent = max(0, min(100, (float) ($item['discount_percent'] ?? 0)));
            $lineTotal = static::calculateLineTotal($qty, $unitPrice, $discountPercent);

            $item['item_name'] = (string) ($product?->name ?? ($item['item_name'] ?? ''));
            $item['qty'] = $qty;
            $item['unit_price'] = $unitPrice;
            $item['discount_percent'] = $discountPercent;
            $item['line_total'] = $lineTotal;

            return $item;
        })->all();

        $data['items'] = $normalizedItems;
        $computedTotal = round(collect($normalizedItems)->sum('line_total'), 2);
        $data['total_amount'] = $computedTotal;
        $data['total_amount_billed'] = $computedTotal;

        return $data;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Order Details')
                            ->schema(static::getDetailsComponents())
                            ->columns(2),
                        Section::make('Order Items')
                            ->afterHeader([
                                Action::make('reset')
                                    ->modalHeading('Are you sure?')
                                    ->modalDescription('All existing items will be removed from the order.')
                                    ->requiresConfirmation()
                                    ->color('danger')
                                    ->action(fn (Set $set) => $set('items', [])),
                            ])
                            ->schema([
                                static::getItemsRepeater(),
                            ]),
                        Section::make('Payment Info')
                            ->schema(static::getPaymentComponents())
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => fn (?Order $record) => $record === null ? 3 : 2]),
                Section::make()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Order date')
                            ->state(fn (Order $record): ?string => $record->created_at?->diffForHumans()),
                        TextEntry::make('updated_at')
                            ->label('Last modified at')
                            ->state(fn (Order $record): ?string => $record->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Order $record) => $record === null),
            ])
            ->columns(3);
    }

    /**
     * @return array<Component>
     */
    public static function getDetailsComponents(): array
    {
        return [
            TextInput::make('order_number')
                ->label('Number')
                ->default(fn (): string => sprintf('OR-%06d', random_int(1, 999999)))
                ->disabled()
                ->dehydrated()
                ->required()
                ->unique(ignoreRecord: true),
            Select::make('consumer_id')
                ->label('Customer')
                ->relationship('consumer', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('vendor_id')
                ->label('Vendor')
                ->relationship('vendor', 'name')
                ->searchable()
                ->preload()
                ->required(),
            ToggleButtons::make('status')
                ->inline()
                ->options(OrderStatus::class)
                ->default(OrderStatus::New->value)
                ->required(),
            Select::make('currency')
                ->options([
                    'EUR' => 'Euro',
                    'USD' => 'US Dollar',
                    'INR' => 'Indian Rupee',
                ])
                ->default('EUR')
                ->required(),
            Textarea::make('notes')->columnSpanFull(),
            AddressForm::make('billing_address', 'Billing Address')->columnSpanFull(),
            AddressForm::make('shipping_address', 'Shipping Address')->columnSpanFull(),
        ];
    }

    /**
     * @return array<Component>
     */
    public static function getPaymentComponents(): array
    {
        return [
            Select::make('payment_method')
                ->options([
                    'cash' => 'Cash',
                    'card' => 'Card',
                    'bank_transfer' => 'Bank Transfer',
                    'upi' => 'UPI',
                    'wallet' => 'Wallet',
                ])
                ->default('cash')
                ->required(),
            TextInput::make('payment_reference_number')
                ->label('Payment Reference Number')
                ->maxLength(120)
                ->placeholder('Txn / UTR / Ref #'),
            Select::make('payment_status')
                ->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'failed' => 'Failed',
                ])
                ->default('pending')
                ->required(),
            TextInput::make('total_amount_billed')
                ->numeric()
                ->readOnly()
                ->dehydrated(false)
                ->default(0),
            Placeholder::make('bill_view')
                ->label('Bill View')
                ->content(function (callable $get): HtmlString {
                    $orderNumber = (string) ($get('order_number') ?: '-');
                    $paymentMethod = (string) ($get('payment_method') ?: '-');
                    $paymentRef = (string) ($get('payment_reference_number') ?: '-');
                    $paymentStatus = (string) ($get('payment_status') ?: '-');
                    $currency = (string) ($get('currency') ?: 'EUR');
                    $items = is_array($get('items')) ? $get('items') : [];
                    $productNames = Product::query()
                        ->whereIn('id', collect($items)->pluck('product_id')->filter()->all())
                        ->pluck('name', 'id');

                    $rows = collect($items)->map(function (array $item) use ($productNames): string {
                        $product = e((string) ($item['item_name'] ?? $productNames[(int) ($item['product_id'] ?? 0)] ?? '-'));
                        $qty = e((string) ($item['qty'] ?? 0));
                        $unitPrice = e((string) ($item['unit_price'] ?? 0));
                        $lineTotal = e((string) ($item['line_total'] ?? 0));

                        return "<tr><td>{$product}</td><td>{$qty}</td><td>{$unitPrice}</td><td>{$lineTotal}</td></tr>";
                    })->implode('');

                    if ($rows === '') {
                        $rows = '<tr><td colspan="4">No items selected.</td></tr>';
                    }

                    $total = round(collect($items)->sum(fn (array $item): float => (float) ($item['line_total'] ?? 0)), 2);

                    return new HtmlString("
                        <div style='display:grid;gap:8px'>
                            <div><strong>Order Number:</strong> ".e($orderNumber)."</div>
                            <div><strong>Payment Method:</strong> ".e($paymentMethod)."</div>
                            <div><strong>Payment Reference Number:</strong> ".e($paymentRef)."</div>
                            <div><strong>Payment Status:</strong> ".e($paymentStatus)."</div>
                            <div style='overflow:auto'>
                                <table style='width:100%;border-collapse:collapse'>
                                    <thead>
                                        <tr>
                                            <th style='text-align:left;padding:6px;border-bottom:1px solid #ddd'>Product Name</th>
                                            <th style='text-align:left;padding:6px;border-bottom:1px solid #ddd'>Quantity</th>
                                            <th style='text-align:left;padding:6px;border-bottom:1px solid #ddd'>Unit Price</th>
                                            <th style='text-align:left;padding:6px;border-bottom:1px solid #ddd'>Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>{$rows}</tbody>
                                </table>
                            </div>
                            <div><strong>Total Amount Billed:</strong> ".e(number_format($total, 2))." ".e($currency)."</div>
                        </div>
                    ");
                })
                ->columnSpanFull(),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('items')
            ->relationship()
            ->table([
                TableColumn::make('Product'),
                TableColumn::make('Quantity')->width(100),
                TableColumn::make('Unit Price')->width(110),
                TableColumn::make('Discount %')->width(110),
                TableColumn::make('Line Total')->width(120),
            ])
            ->defaultItems(1)
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => static::normalizeItemData($data))
            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => static::normalizeItemData($data))
            ->schema([
                Select::make('product_id')
                    ->label('Product')
                    ->options(Product::query()->where('status', 'active')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                        if (! $state) {
                            $set('unit_price', 0);
                            $set('line_total', 0);
                            return;
                        }

                        $product = Product::query()->find($state);
                        $qty = (float) ($get('qty') ?: 1);
                        $unitPrice = (float) ($product?->base_price ?? 0);
                        $discountPercent = (float) ($get('discount_percent') ?: 0);

                        $set('unit_price', $unitPrice);
                        $set('line_total', static::calculateLineTotal($qty, $unitPrice, $discountPercent));
                    })
                    ->required(),
                TextInput::make('qty')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(1)
                    ->readOnly(false)
                    ->disabled(false)
                    ->live()
                    ->afterStateHydrated(function ($state, callable $set, callable $get): void {
                        if (blank($state)) {
                            $set('qty', 1);
                        }
                        static::recalculateLineTotal($set, $get);
                    })
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculateLineTotal($set, $get))
                    ->required(),
                TextInput::make('unit_price')
                    ->numeric()
                    ->readOnly()
                    ->required(),
                TextInput::make('discount_percent')
                    ->label('Discount %')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculateLineTotal($set, $get))
                    ->required(),
                TextInput::make('line_total')
                    ->numeric()
                    ->readOnly()
                    ->dehydrateStateUsing(function ($state, callable $get): float {
                        return static::calculateLineTotal(
                            (float) ($get('qty') ?: 0),
                            (float) ($get('unit_price') ?: 0),
                            (float) ($get('discount_percent') ?: 0)
                        );
                    })
                    ->default(0),
            ])
            ->extraItemActions([
                Action::make('openProduct')
                    ->tooltip('Open product')
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->url(function (array $arguments, Repeater $component): ?string {
                        $itemData = $component->getRawItemState($arguments['item']);
                        $product = Product::find($itemData['product_id'] ?? null);

                        return $product ? ShopProductResource::getUrl('view', ['record' => $product]) : null;
                    }, shouldOpenInNewTab: true)
                    ->hidden(fn (array $arguments, Repeater $component): bool => blank($component->getRawItemState($arguments['item'])['product_id'] ?? null)),
            ])
            ->columns(5)
            ->live()
            ->afterStateUpdated(function ($state, callable $set): void {
                $items = is_array($state) ? $state : [];

                $total = collect($items)->sum(fn (array $item): float => (float) ($item['line_total'] ?? 0));
                $rounded = round($total, 2);
                $set('total_amount', $rounded);
                $set('total_amount_billed', $rounded);
            })
            ->columnSpanFull();
    }
}
