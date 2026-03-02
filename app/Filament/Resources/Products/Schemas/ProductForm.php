<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product')
                    ->schema([
                        Select::make('manufacturer_id')
                            ->options(User::query()->where('role', 'manufacturer')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('category_id')
                            ->options(Category::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        TextInput::make('sku')->required()->maxLength(100)->unique(ignoreRecord: true),
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('base_price')->numeric()->required()->default(0),
                        TextInput::make('unit')->default('pcs'),
                        Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive', 'draft' => 'Draft'])
                            ->default('draft'),
                        Toggle::make('available_for_distributor')->default(true),
                        Textarea::make('description')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
