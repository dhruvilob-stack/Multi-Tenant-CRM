<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Organization Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Organization Email (Login)')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('admin_contact_email')
                    ->label('Organization Admin Gmail')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug (for route & DB suffix)')
                    ->placeholder('adidas')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (callable $set, mixed $state): void {
                        $slug = static::sanitizeSlug($state);
                        $set('slug', $slug);
                        $set('slug_preview', $slug);
                    })
                    ->helperText('Tenant route: http://127.0.0.1:8000/{slug}/login and DB: tenant_{slug}')
                    ->maxLength(120),
                TextInput::make('domain')
                    ->label('Domain')
                    ->placeholder('adidas.multi-tenant-crm')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set, mixed $state): void {
                        $set('domain', static::normalizeDomain($state));
                    })
                    ->maxLength(255),
                TextInput::make('slug_preview')
                    ->label('Slug Preview')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn ($state, callable $get) => static::sanitizeSlug($get('slug') ?: $state))
                    ->helperText('Tenant login URL will be: http://127.0.0.1:8000/{slug}/login'),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(8))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->same('password_confirmation')
                    ->dehydrated(fn (mixed $state): bool => filled($state)),
                TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->requiredWith('password')
                    ->dehydrated(false),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ])
                    ->default('inactive')
                    ->required(),
            ])
            ->columns(2);
    }

    private static function sanitizeSlug(mixed $slug): string
    {
        $value = Str::slug(trim((string) $slug));

        return $value;
    }

    private static function normalizeDomain(mixed $domain): string
    {
        $value = strtolower(trim((string) $domain));
        if ($value === '') {
            return '';
        }

        return $value;
    }
}
