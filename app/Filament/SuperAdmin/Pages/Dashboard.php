<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\Concerns\HasDashboardArrangement;
use App\Filament\Widgets\FeaturesOverview;
use App\Filament\Widgets\OrganizationRevenueGrowthChart;
use App\Filament\Widgets\PlatformAuditEventsByTenantChart;
use App\Filament\Widgets\SuperAdminPlatformStats;
use App\Models\Organization;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasDashboardArrangement;
    use HasFiltersForm;

    /**
     * @return int | array<string, int | null>
     */
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->maxDate(fn () => now()),
                        DatePicker::make('endDate')
                            ->maxDate(now()),
                        Select::make('organizationId')
                            ->label('Organization')
                            ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    protected function getDefaultDashboardWidgets(): array
    {
        return [
            SuperAdminPlatformStats::class,
            OrganizationRevenueGrowthChart::class,
            PlatformAuditEventsByTenantChart::class,
            FeaturesOverview::class,
        ];
    }

    public function mount(): void
    {
        $this->mountHasDashboardArrangement();
    }
}
