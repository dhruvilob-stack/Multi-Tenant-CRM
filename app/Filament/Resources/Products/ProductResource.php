<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $slug = 'products';
    protected static ?string $recordTitleAttribute = 'name';
    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';
    protected static ?string $navigationLabel = 'Products';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::SUPER_ADMIN,
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
            UserRole::VENDOR,
            UserRole::CONSUMER,
        ], true);
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function canEdit($record): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function canDelete($record): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || AccessMatrix::isSuper($user)) {
            return $query;
        }

        return $query->whereHas('manufacturer', fn (Builder $q) => $q->where('organization_id', $user->organization_id));
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}




