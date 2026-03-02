<?php

namespace App\Filament\Resources\ProductChangeRequests\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProductChangeRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('manufacturer_id')->default(fn () => auth()->id()),
                Hidden::make('organization_id')->default(fn () => auth()->user()?->organization_id),
                Select::make('product_id')
                    ->options(
                        Product::query()
                            ->whereHas('manufacturer', fn ($q) => $q->where('organization_id', auth()->user()?->organization_id))
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Textarea::make('requested_changes')->required()->columnSpanFull(),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
                    ->default('pending')
                    ->visible(fn () => auth()->user()?->role === 'org_admin'),
                Textarea::make('response_notes')
                    ->columnSpanFull()
                    ->visible(fn () => auth()->user()?->role === 'org_admin'),
            ]);
    }
}
