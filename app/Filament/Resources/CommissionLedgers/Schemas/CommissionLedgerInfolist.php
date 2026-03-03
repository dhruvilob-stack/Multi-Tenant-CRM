<?php

namespace App\Filament\Resources\CommissionLedgers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CommissionLedgerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice.invoice_number')->label('Invoice'),
                TextEntry::make('product.name')->label('Product'),
                TextEntry::make('fromUser.name')->label('From User'),
                TextEntry::make('toUser.name')->label('To User'),
                TextEntry::make('from_role')->badge(),
                TextEntry::make('to_role')->badge(),
                TextEntry::make('commission_type'),
                TextEntry::make('commission_rate'),
                TextEntry::make('basis_amount')->money('USD'),
                TextEntry::make('commission_amount')->money('USD'),
                TextEntry::make('status')->badge(),
                TextEntry::make('created_at')->dateTime(),
            ]);
    }
}
