<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Organization;
use App\Models\CustomRole;
use App\Models\User;
use App\Support\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema, ?string $fixedRole = null): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8),
                Select::make('role')
                    ->options(array_combine(UserRole::all(), UserRole::all()))
                    ->default($fixedRole)
                    ->disabled(filled($fixedRole))
                    ->dehydrated(fn () => blank($fixedRole))
                    ->required(blank($fixedRole)),
                Select::make('custom_role_id')
                    ->label('Custom Role')
                    ->options(CustomRole::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('organization_id')
                    ->label('Organization')
                    ->options(Organization::query()->pluck('name', 'id'))
                    ->default(fn () => auth()->user()?->organization_id)
                    ->disabled(fn () => auth()->user()?->role !== UserRole::SUPER_ADMIN)
                    ->dehydrated(fn () => auth()->user()?->role === UserRole::SUPER_ADMIN)
                    ->searchable()
                    ->preload(),
                Select::make('parent_id')
                    ->label('Invited By')
                    ->options(User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }
}
