<?php

namespace App\Filament\Resources\Distributors;

use App\Filament\Resources\Distributors\Pages\CreateDistributor;
use App\Filament\Resources\Distributors\Pages\EditDistributor;
use App\Filament\Resources\Distributors\Pages\ListDistributors;
use App\Filament\Resources\Distributors\Pages\ViewDistributor;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DistributorResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $slug = 'distributor';
    protected static ?string $navigationLabel = 'Distributors';
    protected static ?string $modelLabel = 'Distributor';
    protected static string|\UnitEnum|null $navigationGroup = 'Structure';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::MANUFACTURER], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::MANUFACTURER, UserRole::DISTRIBUTOR], true);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::MANUFACTURER], true);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        if (AccessMatrix::isSuper($user) || AccessMatrix::isOrgAdmin($user)) {
            return true;
        }
        if ($user?->role === UserRole::MANUFACTURER) {
            return in_array($record->id, AccessMatrix::distributorIdsFor($user), true);
        }

        return $user?->role === UserRole::DISTRIBUTOR && $record->id === $user->id;
    }

    public static function canDelete($record): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('role', UserRole::DISTRIBUTOR);
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
            return $query->whereIn('id', AccessMatrix::distributorIdsFor($user));
        }
        if ($user->role === UserRole::DISTRIBUTOR) {
            return $query->whereKey($user->id);
        }

        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema, UserRole::DISTRIBUTOR);
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
            'index' => ListDistributors::route('/'),
            'create' => CreateDistributor::route('/create'),
            'view' => ViewDistributor::route('/{record}'),
            'edit' => EditDistributor::route('/{record}/edit'),
        ];
    }
}


