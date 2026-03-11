<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Organization;
use App\Models\CustomRole;
use App\Models\User;
use App\Support\OrganizationEmailFormatter;
use App\Support\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema, ?string $fixedRole = null): Schema
    {
        $tenantUser = auth('tenant')->user();
        $autoEmail = $tenantUser?->role === UserRole::ORG_ADMIN && filled($fixedRole);

        $components = [];

        if ($autoEmail) {
            $components[] = Section::make('Manufacturer Details')
                ->schema([
                    TextInput::make('name')
                        ->label('Company Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (callable $set, callable $get) use ($fixedRole): void {
                            if (filled((string) $get('email'))) {
                                return;
                            }

                            $suggested = static::suggestSystemEmail($get('name'), $fixedRole, $get('organization_id'));
                            if ($suggested !== '') {
                                $set('email', $suggested);
                            }
                        }),
                    TextInput::make('email')
                        ->label('Official Gmail')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->readOnly()
                        ->disabled()
                        ->extraAttributes([
                            'readonly' => true,
                            'tabindex' => '-1',
                            'aria-readonly' => 'true',
                        ])
                        ->dehydrated(fn () => true)
                        ->afterStateHydrated(function (callable $set, callable $get) use ($fixedRole): void {
                            if (filled((string) $get('email'))) {
                                return;
                            }

                            $suggested = static::suggestSystemEmail($get('name'), $fixedRole, $get('organization_id'));
                            if ($suggested !== '') {
                                $set('email', $suggested);
                            }
                        })
                        ->helperText('System-generated login email.'),
                ]);

            $components[] = Section::make('Admin Details')
                ->schema([
                    TextInput::make('admin_first_name')
                        ->label('First Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('admin_last_name')
                        ->label('Last Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('contact_email')
                        ->label('Actual Gmail')
                        ->email()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->maxLength(255),
                ]);
        } else {
            $components[] = TextInput::make('name')
                ->required()
                ->maxLength(255);

            $components[] = TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255);

            $components[] = TextInput::make('contact_email')
                ->label('Actual Gmail')
                ->email()
                ->maxLength(255);
        }

        $components[] = TextInput::make('password')
            ->password()
            ->dehydrated(fn (?string $state): bool => filled($state))
            ->required(fn (string $operation): bool => $operation === 'create')
            ->minLength(8);

        $components[] = Select::make('role')
            ->options(array_combine(UserRole::all(), UserRole::all()))
            ->default($fixedRole)
            ->disabled(filled($fixedRole))
            ->dehydrated(fn () => blank($fixedRole))
            ->required(blank($fixedRole));

        $components[] = Select::make('custom_role_id')
            ->label('Custom Role')
            ->options(CustomRole::query()->where('is_active', true)->pluck('name', 'id'))
            ->searchable()
            ->preload();

        $components[] = Select::make('organization_id')
            ->label('Organization')
            ->options(Organization::query()->pluck('name', 'id'))
            ->default(fn () => auth('tenant')->user()?->organization_id)
            ->disabled(fn () => auth('tenant')->user()?->role !== UserRole::SUPER_ADMIN)
            ->dehydrated(fn () => auth('tenant')->user()?->role === UserRole::SUPER_ADMIN)
            ->searchable()
            ->preload()
            ->live(onBlur: true)
            ->afterStateUpdated(function (callable $set, callable $get) use ($fixedRole, $autoEmail): void {
                if (! $autoEmail) {
                    return;
                }

                if (filled((string) $get('email'))) {
                    return;
                }

                $suggested = static::suggestSystemEmail($get('name'), $fixedRole, $get('organization_id'));
                if ($suggested !== '') {
                    $set('email', $suggested);
                }
            });

        $components[] = Select::make('parent_id')
            ->label('Invited By')
            ->options(User::query()->pluck('name', 'id'))
            ->searchable()
            ->preload();

        $components[] = Select::make('status')
            ->options([
                'pending' => 'Pending',
                'active' => 'Active',
                'inactive' => 'Inactive',
            ])
            ->default('active')
            ->required();

        return $schema->components($components);
    }

    private static function suggestSystemEmail(mixed $name, ?string $fixedRole, mixed $organizationId): string
    {
        $role = $fixedRole ?: null;
        $name = trim((string) $name);

        if ($role === null || $name === '') {
            return '';
        }

        $organization = null;
        if (filled($organizationId)) {
            $organization = Organization::query()->find((int) $organizationId);
        }

        if (! $organization) {
            $organization = auth('tenant')->user()?->organization;
        }

        $domainSource = $organization?->email ?: 'example.com';

        return OrganizationEmailFormatter::suggestEmail($name, $role, $domainSource);
    }
}
