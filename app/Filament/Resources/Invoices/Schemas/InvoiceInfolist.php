<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice_number'),
                TextEntry::make('status')->badge(),
                TextEntry::make('payment_method')->badge(),
                TextEntry::make('quotation.vendor.organization.name')->label('Organization'),
                TextEntry::make('quotation.vendor.name')->label('Vendor'),
                TextEntry::make('contact_name')->label('Consumer'),
                TextEntry::make('billing_address.street')->label('Billing Address')->columnSpanFull(),
                TextEntry::make('shipping_address.street')->label('Shipping Address')->columnSpanFull(),
                TextEntry::make('grand_total')->money('USD'),
                TextEntry::make('received_amount')->money('USD'),
                TextEntry::make('balance')->money('USD'),
                RepeatableEntry::make('items')
                    ->schema([
                        TextEntry::make('item_name')->label('Product'),
                        TextEntry::make('qty')->numeric(),
                        TextEntry::make('selling_price')->money('USD')->label('Unit Cost'),
                        TextEntry::make('total')->money('USD')->label('Total Cost'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
