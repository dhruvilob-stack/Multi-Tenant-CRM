<?php

namespace App\Filament\SuperAdmin\Resources\Tenants;

use App\Filament\SuperAdmin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\SuperAdmin\Resources\Tenants\Pages\EditTenant;
use App\Filament\SuperAdmin\Resources\Tenants\Pages\ListTenants;
use App\Filament\SuperAdmin\Resources\Tenants\Pages\ViewTenant;
use App\Filament\SuperAdmin\Resources\Tenants\Schemas\TenantForm;
use App\Filament\SuperAdmin\Resources\Tenants\Schemas\TenantInfolist;
use App\Filament\SuperAdmin\Resources\Tenants\Tables\TenantsTable;
use App\Models\Organization;
use App\Services\TenantSyncService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantResource extends Resource
{
    protected static ?string $model = Organization::class;
    protected static ?string $slug = 'tenants';
    protected static string|\UnitEnum|null $navigationGroup = 'Tenant Management';
    protected static ?string $navigationLabel = 'Tenants';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TenantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        app(TenantSyncService::class)->backfillMissing();

        return parent::getEloquentQuery();
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
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'view' => ViewTenant::route('/{record}'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}




