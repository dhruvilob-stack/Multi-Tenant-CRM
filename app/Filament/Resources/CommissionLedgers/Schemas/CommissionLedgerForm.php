<?php

namespace App\Filament\Resources\CommissionLedgers\Schemas;

use App\Models\Invoice;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CommissionLedgerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_id')
                    ->options(Invoice::query()->pluck('invoice_number', 'id'))
                    ->required(),
                TextInput::make('from_role')->required(),
                TextInput::make('to_role')->required(),
                TextInput::make('commission_type')->required(),
                TextInput::make('commission_rate')->numeric()->required(),
                TextInput::make('basis_amount')->numeric()->required(),
                TextInput::make('commission_amount')->numeric()->required(),
                TextInput::make('status')->default('accrued')->required(),
            ]);
    }
}
