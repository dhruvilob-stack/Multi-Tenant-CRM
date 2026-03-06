<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use App\Models\Inventory;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class StockMovementsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getHeading(): ?string
    {
        return __('filament.admin.pages.stock_movements.heading');
    }

    public function getDescription(): ?string
    {
        return __('filament.admin.pages.stock_movements.description');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getMovementQuery())
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product')
                    ->state(fn (AuditLog $record): string => (string) ($record->auditable?->product?->name ?? 'Unmapped'))
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->whereHasMorph(
                            'auditable',
                            [Inventory::class],
                            fn (Builder $inventoryQuery): Builder => $inventoryQuery->whereHas(
                                'product',
                                fn (Builder $productQuery): Builder => $productQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('sku', 'like', "%{$search}%"),
                            ),
                        ),
                    )
                    ->placeholder('Unmapped')
                    ->toggleable(),
                TextColumn::make('auditable_id')
                    ->label('Inventory ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Str::headline($state) : 'Unknown')
                    ->badge()
                    ->color(function (?string $state): string {
                        return match ($state) {
                            'created' => 'success',
                            'updated' => 'warning',
                            'deleted' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->sortable(),
                TextColumn::make('performed_by')
                    ->label('Performed By')
                    ->placeholder('System')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('performed_role')
                    ->label('Role')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? Str::headline($state) : 'Unknown')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->placeholder('N/A')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('M d, Y h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Event')
                    ->multiple()
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
                SelectFilter::make('performed_role')
                    ->label('Role')
                    ->multiple()
                    ->options(fn (): array => $this->getPerformedRoleOptions()),
            ])
            ->deferFilters(false)
            ->emptyStateHeading('No stock movement logs yet')
            ->emptyStateDescription('New inventory create, update, and delete activity will appear here.');
    }

    private function getMovementQuery(): Builder
    {
        $organizationId = auth()->user()?->organization_id;

        if (! filled($organizationId)) {
            return AuditLog::query()->whereRaw('1 = 0');
        }

        return AuditLog::query()
            ->where('auditable_type', Inventory::class)
            ->whereHasMorph(
                'auditable',
                [Inventory::class],
                fn (Builder $query): Builder => $query->whereHas(
                    'product.manufacturer',
                    fn (Builder $manufacturerQuery): Builder => $manufacturerQuery->where('organization_id', $organizationId),
                ),
            )
            ->with([
                'auditable' => function (MorphTo $morphTo): void {
                    $morphTo->morphWith([
                        Inventory::class => ['product:id,name,sku'],
                    ]);
                },
            ]);
    }

    /**
     * @return array<string, string>
     */
    private function getPerformedRoleOptions(): array
    {
        return $this->getMovementQuery()
            ->clone()
            ->whereNotNull('performed_role')
            ->where('performed_role', '!=', '')
            ->distinct('performed_role')
            ->orderBy('performed_role')
            ->pluck('performed_role', 'performed_role')
            ->mapWithKeys(fn (string $role): array => [$role => Str::headline($role)])
            ->all();
    }
}
