<?php

namespace App\Filament\Resources\Manufacturers;

use App\Filament\Resources\Manufacturers\Pages\CreateManufacturer;
use App\Filament\Resources\Manufacturers\Pages\EditManufacturer;
use App\Filament\Resources\Manufacturers\Pages\ListManufacturers;
use App\Filament\Resources\Manufacturers\Pages\ViewManufacturer;
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

class ManufacturerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'manufacturers';

    protected static ?string $navigationLabel = 'Manufacturers';

    protected static ?string $modelLabel = 'Manufacturer';

    protected static string|\UnitEnum|null $navigationGroup = 'Structure';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog8Tooth;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::MANUFACTURER], true);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (AccessMatrix::isSuper($user) || AccessMatrix::isOrgAdmin($user)) {
            return true;
        }

        return $user?->role === UserRole::MANUFACTURER && $record->id === $user->id;
    }

    public static function canDelete($record): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('role', UserRole::MANUFACTURER);
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
            return $query->whereKey($user->id);
        }

        return $query->whereRaw('1=0');
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = 'manufacturer';
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
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
            'index' => ListManufacturers::route('/'),
            'create' => CreateManufacturer::route('/create'),
            'view' => ViewManufacturer::route('/{record}'),
            'edit' => EditManufacturer::route('/{record}/edit'),
        ];
    }
}




