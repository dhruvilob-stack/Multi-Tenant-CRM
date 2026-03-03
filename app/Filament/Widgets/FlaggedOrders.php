<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class FlaggedOrders extends BaseWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return static::canUseResource(OrderResource::class);
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

        $query = OrderResource::getEloquentQuery()
            ->where(function (Builder $query): void {
                $query->where(function (Builder $q): void {
                    $q->where('status', 'pending')
                        ->where('created_at', '<=', now()->subDays(3));
                })->orWhere(function (Builder $q): void {
                    $q->where('status', 'processing')
                        ->where('created_at', '<=', now()->subDays(7));
                });
            })
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
            ->when(filled($orderStatuses), fn ($q) => $q->whereIn('status', $orderStatuses))
            ->with(['consumer']);

        return $table
            ->query($query)
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('consumer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('days_old')
                    ->label('Days Old')
                    ->state(fn (Order $record): int => (int) $record->created_at?->diffInDays(now()))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('created_at', $direction === 'asc' ? 'desc' : 'asc')),
                TextColumn::make('issue')
                    ->label('Issue')
                    ->state(fn (Order $record): string => $record->status === 'pending' ? 'Awaiting processing' : 'Stuck in processing')
                    ->badge()
                    ->color(fn (Order $record): string => $record->status === 'pending' ? 'warning' : 'danger'),
            ]);
    }
}
