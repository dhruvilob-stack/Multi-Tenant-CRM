<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->schema([
                        TextEntry::make('order_number'),
                        TextEntry::make('consumer.name')->label('Consumer'),
                        TextEntry::make('vendor.name')->label('Vendor'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('currency')->badge(),
                        TextEntry::make('payment_method')->badge(),
                        TextEntry::make('payment_reference_number')->label('Payment Ref')->placeholder('-'),
                        TextEntry::make('payment_status')->badge(),
                        TextEntry::make('total_amount_billed')->label('Total Billed')->money(fn ($record) => (string) ($record->currency ?? 'EUR')),
                        TextEntry::make('paid_at')->dateTime(),
                        TextEntry::make('invoice.invoice_number')->label('Invoice'),
                    ])
                    ->columns(2),
                Section::make('Addresses')
                    ->schema([
                        TextEntry::make('billing_address.street')->label('Billing Street')->placeholder('-'),
                        TextEntry::make('shipping_address.street')->label('Shipping Street')->placeholder('-'),
                        TextEntry::make('billing_address.city')->label('Billing City')->placeholder('-'),
                        TextEntry::make('shipping_address.city')->label('Shipping City')->placeholder('-'),
                        TextEntry::make('billing_address.state')->label('Billing State')->placeholder('-'),
                        TextEntry::make('shipping_address.state')->label('Shipping State')->placeholder('-'),
                        TextEntry::make('billing_address.postal_code')->label('Billing Postal')->placeholder('-'),
                        TextEntry::make('shipping_address.postal_code')->label('Shipping Postal')->placeholder('-'),
                        TextEntry::make('billing_address.country')->label('Billing Country')->placeholder('-'),
                        TextEntry::make('shipping_address.country')->label('Shipping Country')->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('item_name')->label('Item'),
                                TextEntry::make('qty')->numeric(),
                                TextEntry::make('unit_price')->money(fn ($record) => (string) ($record->order?->currency ?? 'EUR')),
                                TextEntry::make('discount_percent')->label('Discount')->suffix('%'),
                                TextEntry::make('line_total')->money(fn ($record) => (string) ($record->order?->currency ?? 'EUR')),
                            ])
                            ->columns(5),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')->placeholder('No notes'),
                    ]),
            ])
            ->columns(2);
    }
}
