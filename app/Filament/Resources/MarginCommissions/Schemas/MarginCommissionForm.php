<?php

namespace App\Filament\Resources\MarginCommissions\Schemas;

use App\Models\Product;
use App\Support\UserRole;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MarginCommissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('organization_id')
                    ->default(fn () => auth('tenant')->user()?->organization_id),
                Hidden::make('category_id')
                    ->default(null),
                Select::make('rule_type')
                    ->options([
                        'global' => 'Global Rule',
                        'category' => 'Category Rule',
                        'product' => 'Product Rule',
                    ])
                    ->default('global')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if ($state === 'global' || $state === 'category') {
                            $set('product_id', null);
                            $set('category_id', null);
                        }
                    }),
                Select::make('product_id')
                    ->options(function (): array {
                        $user = auth('tenant')->user();
                        $query = Product::query()->with('manufacturer:id,organization_id');

                        if ($user?->role !== UserRole::SUPER_ADMIN) {
                            $query->whereHas('manufacturer', fn ($q) => $q->where('organization_id', $user?->organization_id));
                        }

                        return $query->pluck('name', 'id')->all();
                    })
                    ->searchable()
                    ->preload()
                    ->hidden(fn (Get $get): bool => $get('rule_type') !== 'product')
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                        $productId = is_numeric($state) ? (int) $state : null;
                        if (! $productId) {
                            if ($get('rule_type') === 'product') {
                                $set('category_id', null);
                            }

                            return;
                        }

                        $set('rule_type', 'product');
                        $linkedCategoryId = Product::query()
                            ->whereKey($productId)
                            ->value('category_id');

                        $set('category_id', $linkedCategoryId ?: null);
                    }),
                TextInput::make('priority')
                    ->numeric()
                    ->default(100)
                    ->minValue(1)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Rule Active')
                    ->default(true),
                Hidden::make('from_role')
                    ->default('manufacturer'),
                Hidden::make('to_role')
                    ->default('distributor'),
                Select::make('role')
                    ->label('Role')
                    ->options([
                        'manufacturer' => 'Manufacturer',
                        'distributor' => 'Distributor',
                        'vendor' => 'Vendor',
                    ])
                    ->required()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Get $get, Set $set, $state): void {
                        if (filled($state)) {
                            return;
                        }

                        $fromRole = (string) ($get('from_role') ?? '');
                        if ($fromRole !== '') {
                            $set('role', $fromRole);
                        }
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $role = in_array((string) $state, ['manufacturer', 'distributor', 'vendor'], true)
                            ? (string) $state
                            : 'manufacturer';

                        $set('from_role', $role);
                        $set('to_role', self::mapRoleToToRole($role));
                    }),
                Select::make('commission_type')
                    ->options(['percentage' => 'Percentage', 'fixed' => 'Fixed'])
                    ->default('percentage')
                    ->required(),
                TextInput::make('commission_value')->numeric()->required()->default(0),
            ]);
    }

    private static function mapRoleToToRole(string $role): string
    {
        return match ($role) {
            'manufacturer' => 'distributor',
            'distributor' => 'vendor',
            'vendor' => 'consumer',
            default => 'distributor',
        };
    }
}
