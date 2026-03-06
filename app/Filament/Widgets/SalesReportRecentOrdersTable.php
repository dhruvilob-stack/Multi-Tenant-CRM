<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Models\Order;
use App\Support\SystemSettings;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SalesReportRecentOrdersTable extends TableWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        return static::canUseResource(OrderResource::class);
    }

    public function getHeading(): ?string
    {
        return 'Recent Orders';
    }

    public function getDescription(): ?string
    {
        return 'Interactive order list. You can search, sort, and browse recent transactions.';
    }

    public function table(Table $table): Table
    {
        $startDate = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfDay()
            : null;
        $endDate = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfDay()
            : null;
        $orderStatuses = $this->pageFilters['orderStatuses'] ?? null;

        return $table
            ->query(
                OrderResource::getEloquentQuery()
                    ->with(['vendor:id,name', 'consumer:id,name'])
                    ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
                    ->when(filled($orderStatuses), fn ($q) => $q->whereIn('status', $orderStatuses)),
            )
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('consumer.name')
                    ->label('Customer')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Str::headline($state) : 'Unknown')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money(fn (): string => SystemSettings::currencyForCurrentUser())
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created Date')
                    ->dateTime('M d, Y h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
