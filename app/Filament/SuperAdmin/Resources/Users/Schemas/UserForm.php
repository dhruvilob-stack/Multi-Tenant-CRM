<?php

namespace App\Filament\SuperAdmin\Resources\Users\Schemas;

use App\Models\CustomRole;
use App\Models\Organization;
use App\Support\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(8))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state): bool => filled($state))
                    ->same('password_confirmation'),
                TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
                Select::make('role')
                    ->options([
                        UserRole::ORG_ADMIN => 'Organization Admin',
                        UserRole::MANUFACTURER => 'Manufacturer',
                        UserRole::DISTRIBUTOR => 'Distributor',
                        UserRole::VENDOR => 'Vendor',
                        UserRole::CONSUMER => 'Consumer',
                    ])
                    ->default(UserRole::ORG_ADMIN)
                    ->live()
                    ->required(),
                Toggle::make('create_new_organization')
                    ->label('Create New Organization')
                    ->default(false)
                    ->live()
                    ->visible(fn (callable $get): bool => $get('role') === UserRole::ORG_ADMIN),
                Select::make('organization_id')
                    ->label('Organization')
                    ->options(Organization::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(fn (callable $get): bool => ! ($get('role') === UserRole::ORG_ADMIN && (bool) $get('create_new_organization')))
                    ->visible(fn (callable $get): bool => ! ($get('role') === UserRole::ORG_ADMIN && (bool) $get('create_new_organization'))),
                TextInput::make('new_organization_name')
                    ->label('New Organization Name')
                    ->required(fn (callable $get): bool => $get('role') === UserRole::ORG_ADMIN && (bool) $get('create_new_organization'))
                    ->visible(fn (callable $get): bool => $get('role') === UserRole::ORG_ADMIN && (bool) $get('create_new_organization'))
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        if (! $state) {
                            return;
                        }

                        $set('new_organization_slug', Str::slug($state));
                    }),
                TextInput::make('new_organization_slug')
                    ->label('New Organization Slug')
                    ->required(fn (callable $get): bool => $get('role') === UserRole::ORG_ADMIN && (bool) $get('create_new_organization'))
                    ->visible(fn (callable $get): bool => $get('role') === UserRole::ORG_ADMIN && (bool) $get('create_new_organization'))
                    ->maxLength(255),
                TextInput::make('new_organization_email')
                    ->label('New Organization Email')
                    ->email()
                    ->visible(fn (callable $get): bool => $get('role') === UserRole::ORG_ADMIN && (bool) $get('create_new_organization')),
                TextInput::make('new_organization_phone')
                    ->label('New Organization Phone')
                    ->visible(fn (callable $get): bool => $get('role') === UserRole::ORG_ADMIN && (bool) $get('create_new_organization')),
                TextInput::make('new_organization_address')
                    ->label('New Organization Address')
                    ->visible(fn (callable $get): bool => $get('role') === UserRole::ORG_ADMIN && (bool) $get('create_new_organization')),
                Select::make('custom_role_id')
                    ->label('Custom Role')
                    ->options(CustomRole::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending (Invitation)',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->default('active')
                    ->required(),
            ])
            ->columns(2);
    }
}
