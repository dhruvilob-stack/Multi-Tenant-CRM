<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Product;
use App\Models\User;
use App\Support\UserRole;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Details')
                    ->schema([
                        TextInput::make('order_number')
                            ->required()
                            ->default(fn (): string => sprintf('ORD-%s-%04d', now()->format('Y'), random_int(1, 9999)))
                            ->unique(ignoreRecord: true),
                        Select::make('consumer_id')
                            ->options(function (): array {
                                $user = auth()->user();
                                $query = User::query()->where('role', UserRole::CONSUMER);

                                if ($user?->role !== UserRole::SUPER_ADMIN) {
                                    $query->where('organization_id', $user?->organization_id);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('vendor_id')
                            ->options(function (): array {
                                $user = auth()->user();
                                $query = User::query()->where('role', UserRole::VENDOR);

                                if ($user?->role !== UserRole::SUPER_ADMIN) {
                                    $query->where('organization_id', $user?->organization_id);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required()
                            ->disabled(),
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
                        Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'failed' => 'Failed',
                            ])
                            ->default('pending')
                            ->required(),
                        TextInput::make('total_amount')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Order Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->defaultItems(1)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(function (): array {
                                        $user = auth()->user();
                                        $query = Product::query()->where('status', 'active');

                                        if ($user?->role !== UserRole::SUPER_ADMIN) {
                                            $query->whereHas('manufacturer', fn (Builder $q) => $q->where('organization_id', $user?->organization_id));
                                        }

                                        return $query->pluck('name', 'id')->all();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        if (! $state) {
                                            return;
                                        }

                                        $product = Product::query()->find($state);

                                        $qty = (float) ($get('qty') ?: 1);
                                        $unitPrice = (float) ($product?->base_price ?? 0);

                                        $set('item_name', $product?->name ?? '');
                                        $set('unit_price', $unitPrice);
                                        $set('line_total', round($qty * $unitPrice, 2));
                                    })
                                    ->required(),
                                TextInput::make('item_name')
                                    ->required(),
                                TextInput::make('qty')
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        $qty = max((float) ($state ?: 0), 0);
                                        $unitPrice = (float) ($get('unit_price') ?: 0);
                                        $set('line_total', round($qty * $unitPrice, 2));
                                    })
                                    ->required(),
                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        $qty = max((float) ($get('qty') ?: 0), 0);
                                        $unitPrice = (float) ($state ?: 0);
                                        $set('line_total', round($qty * $unitPrice, 2));
                                    })
                                    ->required(),
                                TextInput::make('line_total')
                                    ->numeric()
                                    ->readOnly()
                                    ->default(0),
                            ])
                            ->columns(5)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $items = is_array($state) ? $state : [];

                                $total = collect($items)
                                    ->sum(fn (array $item): float => (float) ($item['line_total'] ?? 0));

                                $set('total_amount', round($total, 2));
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Addresses')
                    ->schema([
                        Textarea::make('billing_address.street')->label('Billing Street')->required(),
                        Textarea::make('shipping_address.street')->label('Shipping Street')->required(),
                        TextInput::make('billing_address.city')->label('Billing City')->required(),
                        TextInput::make('shipping_address.city')->label('Shipping City')->required(),
                        TextInput::make('billing_address.state')->label('Billing State'),
                        TextInput::make('shipping_address.state')->label('Shipping State'),
                        TextInput::make('billing_address.postal_code')->label('Billing Postal Code'),
                        TextInput::make('shipping_address.postal_code')->label('Shipping Postal Code'),
                        TextInput::make('billing_address.country')->label('Billing Country'),
                        TextInput::make('shipping_address.country')->label('Shipping Country'),
                    ])
                    ->columns(2),
            ]);
    }
}
