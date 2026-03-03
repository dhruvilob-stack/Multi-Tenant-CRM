<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order_number'),
                TextEntry::make('consumer.name')->label('Consumer'),
                TextEntry::make('vendor.name')->label('Vendor'),
                TextEntry::make('status')->badge(),
                TextEntry::make('payment_method')->badge(),
                TextEntry::make('payment_status')->badge(),
                TextEntry::make('total_amount')->money('USD'),
                TextEntry::make('paid_at')->dateTime(),
                TextEntry::make('invoice.invoice_number')->label('Invoice'),
                TextEntry::make('billing_address.street')->label('Billing Address')->columnSpanFull(),
                TextEntry::make('shipping_address.street')->label('Shipping Address')->columnSpanFull(),
                RepeatableEntry::make('items')
                    ->schema([
                        TextEntry::make('item_name')->label('Item'),
                        TextEntry::make('qty')->numeric(),
                        TextEntry::make('unit_price')->money('USD'),
                        TextEntry::make('line_total')->money('USD'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
