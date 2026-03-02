<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')->required()->maxLength(36)->unique(ignoreRecord: true),
                TextInput::make('name')->required(),
                TextInput::make('domain')->required()->unique(ignoreRecord: true),
                KeyValue::make('data')->columnSpanFull(),
            ]);
    }
}
