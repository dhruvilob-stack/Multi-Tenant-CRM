<?php

namespace App\Filament\Resources\Vendors;

use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Filament\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Filament\Resources\Vendors\Pages\ViewVendor;
use App\Models\User;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VendorResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $slug = 'vendor';
    protected static ?string $navigationLabel = 'Vendors';
    protected static ?string $modelLabel = 'Vendor';
    protected static string|\UnitEnum|null $navigationGroup = 'Structure';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::DISTRIBUTOR], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR], true);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::DISTRIBUTOR], true);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (AccessMatrix::isSuper($user) || AccessMatrix::isOrgAdmin($user)) {
            return true;
        }
        if ($user?->role === UserRole::DISTRIBUTOR) {
            return in_array($record->id, AccessMatrix::vendorIdsForDistributor($user), true);
        }

        return $user?->role === UserRole::VENDOR && $record->id === $user->id;
    }

    public static function canDelete($record): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('role', UserRole::VENDOR);
        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1=0');
        }
        if (AccessMatrix::isSuper($user)) {
            return $query;
        }
        if ($user->role === UserRole::ORG_ADMIN) {
            return $query->where('organization_id', $user->organization_id);
        }
        if ($user->role === UserRole::MANUFACTURER) {
            return $query->whereIn('id', AccessMatrix::vendorIdsForManufacturer($user));
        }
        if ($user->role === UserRole::DISTRIBUTOR) {
            return $query->whereIn('id', AccessMatrix::vendorIdsForDistributor($user));
        }
        if ($user->role === UserRole::VENDOR) {
            return $query->whereKey($user->id);
        }

        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema, UserRole::VENDOR);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'view' => ViewVendor::route('/{record}'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
}


