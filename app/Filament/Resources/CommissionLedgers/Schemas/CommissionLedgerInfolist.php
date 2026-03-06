<?php

namespace App\Filament\Resources\CommissionLedgers\Schemas;

use App\Support\SystemSettings;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommissionLedgerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Commission Details')
                    ->schema([
                        TextEntry::make('invoice.invoice_number')
                            ->label('Invoice')
                            ->placeholder('-'),
                        TextEntry::make('product.name')
                            ->label('Product')
                            ->placeholder('-'),
                        TextEntry::make('commission_type')
                            ->label('Commission Type')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                    ])
                    ->columns(2),
                Section::make('Commission Parties')
                    ->schema([
                        TextEntry::make('fromUser.name')
                            ->label('From User')
                            ->placeholder('-'),
                        TextEntry::make('toUser.name')
                            ->label('To User')
                            ->placeholder('-'),
                        TextEntry::make('from_role')
                            ->label('From Role')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('to_role')
                            ->label('To Role')
                            ->badge()
                            ->color('primary'),
                    ])
                    ->columns(2),
                Section::make('Commission Amount')
                    ->schema([
                        TextEntry::make('basis_amount')
                            ->label('Basis Amount')
                            ->money(fn (): string => SystemSettings::currencyForCurrentUser()),
                        TextEntry::make('commission_rate')
                            ->label('Commission Rate')
                            ->suffix('%'),
                        TextEntry::make('commission_amount')
                            ->label('Commission Amount')
                            ->money(fn (): string => SystemSettings::currencyForCurrentUser())
                            ->weight('bold'),
                    ])
                    ->columns(3),
                Section::make('Timestamps')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }
}
