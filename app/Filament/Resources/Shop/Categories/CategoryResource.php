<?php

namespace App\Filament\Resources\Shop\Categories;

use App\Filament\Resources\Shop\Categories\Pages\CreateCategory;
use App\Filament\Resources\Shop\Categories\Pages\EditCategory;
use App\Filament\Resources\Shop\Categories\Pages\ListCategories;
use App\Filament\Resources\Shop\Categories\Pages\ViewCategory;
use App\Filament\Resources\Shop\Categories\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\Shop\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Shop\Categories\Schemas\CategoryInfolist;
use App\Filament\Resources\Shop\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'shop/categories';

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CategoryInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'view' => ViewCategory::route('/{record}'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
