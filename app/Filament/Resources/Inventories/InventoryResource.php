<?php

namespace App\Filament\Resources\Inventories;

use App\Filament\Resources\Inventories\Pages\CreateInventory;
use App\Filament\Resources\Inventories\Pages\EditInventory;
use App\Filament\Resources\Inventories\Pages\ListInventories;
use App\Filament\Resources\Inventories\Pages\ViewInventory;
use App\Filament\Resources\Inventories\RelationManagers\AuditLogsRelationManager;
use App\Filament\Resources\Inventories\Schemas\InventoryForm;
use App\Filament\Resources\Inventories\Schemas\InventoryInfolist;
use App\Filament\Resources\Inventories\Tables\InventoriesTable;
use App\Models\Inventory;
use App\Models\User;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;
    protected static ?string $slug = 'inventory';
    protected static string|\UnitEnum|null $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Inventory';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth('tenant')->user()?->role, ['org_admin', 'manufacturer', 'distributor', 'vendor'], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth('tenant')->user()?->role, ['org_admin', 'manufacturer', 'distributor', 'vendor'], true);
    }

    public static function canCreate(): bool
    {
        return in_array(auth('tenant')->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::MANUFACTURER], true);
    }

    public static function canEdit($record): bool
    {
        return in_array(auth('tenant')->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR], true);
    }

    public static function canDelete($record): bool
    {
        return in_array(auth('tenant')->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth('tenant')->user();

        if (! $user || AccessMatrix::isSuper($user)) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($user): void {
            $scoped
                ->whereHas('product.manufacturer', fn (Builder $q) => $q->where('organization_id', $user->organization_id))
                ->orWhere(function (Builder $unmapped) use ($user): void {
                    $unmapped
                        ->whereNull('product_id')
                        ->where('owner_type', User::class)
                        ->whereHasMorph(
                            'owner',
                            [User::class],
                            fn (Builder $owner) => $owner->where('organization_id', $user->organization_id),
                        );
                });
        });
    }

    public static function form(Schema $schema): Schema
    {
        return InventoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InventoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AuditLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventories::route('/'),
            'create' => CreateInventory::route('/create'),
            'view' => ViewInventory::route('/{record}'),
            'edit' => EditInventory::route('/{record}/edit'),
        ];
    }
}
