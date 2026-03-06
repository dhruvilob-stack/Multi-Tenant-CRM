<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('status'),
                TextEntry::make('created_at')->dateTime(),
            ])
            ->columns(2);
    }
}
