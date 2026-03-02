<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('tenant_id')
                    ->label('Tenant ID')
                    ->maxLength(36)
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Auto-generated and linked after saving organization.'),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('email')->email(),
                TextInput::make('phone'),
                TextInput::make('admin_name')
                    ->label('Organization Admin Name')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                TextInput::make('admin_email')
                    ->label('Organization Admin Email')
                    ->email()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->unique('users', 'email'),
                TextInput::make('admin_password')
                    ->label('Organization Admin Password')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(8))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->same('admin_password_confirmation'),
                TextInput::make('admin_password_confirmation')
                    ->label('Confirm Admin Password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active'),
                Textarea::make('address')->columnSpanFull(),
            ])
            ->columns(2);
    }
}
