<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('role'),
                TextEntry::make('customRole.name')->label('Custom Role'),
                TextEntry::make('organization.name')->label('Organization'),
                TextEntry::make('parent.name')->label('Invited By'),
                TextEntry::make('status'),
                TextEntry::make('created_at')->dateTime(),
            ]);
    }
}
