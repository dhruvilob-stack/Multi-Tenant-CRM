<?php

namespace App\Filament\Resources\Consumers;

use App\Filament\Resources\Consumers\Pages\CreateConsumer;
use App\Filament\Resources\Consumers\Pages\EditConsumer;
use App\Filament\Resources\Consumers\Pages\ListConsumers;
use App\Filament\Resources\Consumers\Pages\ViewConsumer;
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

class ConsumerResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $slug = 'consumers';
    protected static ?string $navigationLabel = 'Consumers';
    protected static ?string $modelLabel = 'Consumer';
    protected static string|\UnitEnum|null $navigationGroup = 'Structure';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth('tenant')->user();

        return in_array($user?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::VENDOR], true);
    }

    public static function canViewAny(): bool
    {
        $user = auth('tenant')->user();

        return in_array($user?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::DISTRIBUTOR, UserRole::VENDOR, UserRole::CONSUMER], true);
    }

    public static function canCreate(): bool
    {
        $user = auth('tenant')->user();

        return in_array($user?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::VENDOR], true);
    }

    public static function canEdit($record): bool
    {
        $user = auth('tenant')->user();
        if (AccessMatrix::isSuper($user) || AccessMatrix::isOrgAdmin($user)) {
            return true;
        }
        if ($user?->role === UserRole::VENDOR) {
            return in_array($record->id, AccessMatrix::consumerIdsForVendor($user), true);
        }

        return $user?->role === UserRole::CONSUMER && $record->id === $user->id;
    }

    public static function canDelete($record): bool
    {
        $user = auth('tenant')->user();

        return in_array($user?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('role', UserRole::CONSUMER);
        $user = auth('tenant')->user();
        if (! $user) {
            return $query->whereRaw('1=0');
        }
        if (AccessMatrix::isSuper($user)) {
            return $query;
        }
        if ($user->role === UserRole::ORG_ADMIN) {
            return $query->where('organization_id', $user->organization_id);
        }
        if ($user->role === UserRole::DISTRIBUTOR) {
            return $query->whereIn('id', AccessMatrix::consumerIdsForDistributor($user));
        }
        if ($user->role === UserRole::VENDOR) {
            return $query->whereIn('id', AccessMatrix::consumerIdsForVendor($user));
        }
        if ($user->role === UserRole::CONSUMER) {
            return $query->whereKey($user->id);
        }

        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema, UserRole::CONSUMER);
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
            'index' => ListConsumers::route('/'),
            'create' => CreateConsumer::route('/create'),
            'view' => ViewConsumer::route('/{record}'),
            'edit' => EditConsumer::route('/{record}/edit'),
        ];
    }
}
