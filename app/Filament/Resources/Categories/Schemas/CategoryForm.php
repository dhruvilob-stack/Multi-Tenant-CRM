<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Organization;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->options(Organization::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('name')->required()->maxLength(255),
            ]);
    }
}
