<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice')
                    ->schema([
                        TextEntry::make('invoice_number'),
                        TextEntry::make('order.order_number')->label('Order #')->placeholder('-'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('payment_method')->badge(),
                        TextEntry::make('quotation.vendor.organization.name')->label('Organization'),
                        TextEntry::make('quotation.vendor.name')->label('Vendor'),
                        TextEntry::make('contact_name')->label('Consumer'),
                        TextEntry::make('order.payment_reference_number')->label('Payment Ref')->placeholder('-'),
                        TextEntry::make('currency')->badge(),
                        TextEntry::make('grand_total')->money(fn ($record) => (string) ($record->currency ?? 'USD')),
                    ])
                    ->columns(2),
                Section::make('Addresses')
                    ->schema([
                        TextEntry::make('billing_address.street')->label('Billing Address')->placeholder('-'),
                        TextEntry::make('shipping_address.street')->label('Shipping Address')->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('item_name')->label('Product'),
                                TextEntry::make('qty')->numeric(),
                                TextEntry::make('selling_price')->money(fn ($record) => (string) ($record->invoice?->currency ?? 'USD'))->label('Unit Cost'),
                                TextEntry::make('total')->money(fn ($record) => (string) ($record->invoice?->currency ?? 'USD'))->label('Line Total'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(2);
    }
}
