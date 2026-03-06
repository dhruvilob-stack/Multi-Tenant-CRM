<?php

namespace App\Filament\Resources\Shop\Products\Widgets;

use App\Filament\Resources\Shop\Products\Pages\ListProducts;
use App\Support\SystemSettings;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ProductStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListProducts::class;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Products', $this->getPageTableQuery()->count()),
            Stat::make('Product Inventory', (string) $this->getPageTableQuery()->sum('qty')),
            Stat::make(
                'Average price',
                Number::currency((float) $this->getPageTableQuery()->avg('price'), SystemSettings::currencyForCurrentUser())
            ),
        ];
    }
}
