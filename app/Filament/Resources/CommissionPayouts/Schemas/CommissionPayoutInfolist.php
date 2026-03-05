<?php

namespace App\Filament\Resources\CommissionPayouts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommissionPayoutInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payout Details')
                    ->schema([
                        TextEntry::make('payout_number')->label('Payout #'),
                        TextEntry::make('user.name')->label('Partner'),
                        TextEntry::make('user.role')->label('Role')->badge(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('amount')->money(fn ($record): string => (string) ($record->currency ?? 'USD')),
                        TextEntry::make('payment_method')->badge(),
                        TextEntry::make('reference')->placeholder('-'),
                        TextEntry::make('paid_at')->date()->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Linked Ledger Entries')
                    ->schema([
                        TextEntry::make('items_count')
                            ->label('Entries Linked')
                            ->state(fn ($record): int => (int) $record->items()->count()),
                    ]),
            ]);
    }
}
