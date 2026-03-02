<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Support\AccessMatrix;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $slug = 'users';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Users & Roles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === 'org_admin';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'org_admin';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || AccessMatrix::isSuper($user)) {
            return $query;
        }

        return $query->where('organization_id', $user->organization_id);
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}




