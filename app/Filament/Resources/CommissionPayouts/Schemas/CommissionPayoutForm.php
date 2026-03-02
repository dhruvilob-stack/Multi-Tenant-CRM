<?php

namespace App\Filament\Resources\CommissionPayouts\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CommissionPayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->options(User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('amount')->numeric()->required(),
                TextInput::make('reference'),
                DatePicker::make('paid_at'),
                Textarea::make('notes')->columnSpanFull(),
            ]);
    }
}
