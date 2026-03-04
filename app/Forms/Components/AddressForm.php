<?php

namespace App\Forms\Components;

use App\Enums\CountryCode;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class AddressForm
{
    public static function make(string $statePath, string $title): Section
    {
        return Section::make($title)
            ->schema([
                Grid::make()
                    ->schema([
                        Select::make("{$statePath}.country")
                            ->label('Country')
                            ->options(collect(CountryCode::cases())->mapWithKeys(fn (CountryCode $code) => [$code->value => $code->value])->all())
                            ->searchable(),
                    ]),
                TextInput::make("{$statePath}.street")
                    ->label('Street address')
                    ->maxLength(255),
                Grid::make(3)
                    ->schema([
                        TextInput::make("{$statePath}.city")
                            ->maxLength(255),
                        TextInput::make("{$statePath}.state")
                            ->label('State / Province')
                            ->maxLength(255),
                        TextInput::make("{$statePath}.postal_code")
                            ->label('Zip / Postal code')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
