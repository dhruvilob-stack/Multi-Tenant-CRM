<?php

namespace App\Filament\SuperAdmin\Resources\CustomRoles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomRoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('is_active')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive'),
                TextEntry::make('permissions')
                    ->label('Permissions')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : '')
                    ->columnSpanFull(),
                TextEntry::make('description')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}

