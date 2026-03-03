<?php

namespace App\Filament\Resources\MarginCommissions\Schemas;

use App\Models\Category;
use App\Models\Product;
use App\Support\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MarginCommissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->options(function (): array {
                        $user = auth()->user();
                        $query = Product::query()->with('manufacturer:id,organization_id');

                        if ($user?->role !== UserRole::SUPER_ADMIN) {
                            $query->whereHas('manufacturer', fn ($q) => $q->where('organization_id', $user?->organization_id));
                        }

                        return $query->pluck('name', 'id')->all();
                    })
                    ->searchable()
                    ->preload(),
                Select::make('category_id')
                    ->options(function (): array {
                        $user = auth()->user();
                        $query = Category::query();

                        if ($user?->role !== UserRole::SUPER_ADMIN) {
                            $query->where('organization_id', $user?->organization_id);
                        }

                        return $query->pluck('name', 'id')->all();
                    })
                    ->searchable()
                    ->preload(),
                Select::make('from_role')->options([
                    'manufacturer' => 'Manufacturer',
                    'distributor' => 'Distributor',
                    'vendor' => 'Vendor',
                ])->required(),
                Select::make('to_role')->options([
                    'distributor' => 'Distributor',
                    'vendor' => 'Vendor',
                    'consumer' => 'Consumer',
                ])->required(),
                Select::make('commission_type')
                    ->options(['percentage' => 'Percentage', 'fixed' => 'Fixed'])
                    ->default('percentage')
                    ->required(),
                TextInput::make('commission_value')->numeric()->required()->default(0),
            ]);
    }
}
