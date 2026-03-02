<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('name'),
                TextEntry::make('domain'),
                KeyValueEntry::make('data'),
                TextEntry::make('created_at')->dateTime(),
            ]);
    }
}
