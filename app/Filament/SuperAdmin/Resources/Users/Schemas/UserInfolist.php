<?php

namespace App\Filament\SuperAdmin\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('role'),
                TextEntry::make('organization.name')->label('Organization'),
                TextEntry::make('customRole.name')->label('Custom Role'),
                TextEntry::make('status'),
                TextEntry::make('invitation_accepted_at')->dateTime(),
                TextEntry::make('created_at')->dateTime(),
            ]);
    }
}
