<?php

namespace App\Filament\SuperAdmin\Resources\Users;

use App\Filament\SuperAdmin\Resources\Users\Pages\CreateUser;
use App\Filament\SuperAdmin\Resources\Users\Pages\EditUser;
use App\Filament\SuperAdmin\Resources\Users\Pages\ListUsers;
use App\Filament\SuperAdmin\Resources\Users\Pages\ViewUser;
use App\Filament\SuperAdmin\Resources\Users\Schemas\UserForm;
use App\Filament\SuperAdmin\Resources\Users\Schemas\UserInfolist;
use App\Filament\SuperAdmin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Services\TenantUserSyncService;
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
    protected static ?string $recordTitleAttribute = 'name';
    protected static string|\UnitEnum|null $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'All Users';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

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

    public static function getEloquentQuery(): Builder
    {
        app(TenantUserSyncService::class)->syncAllTenantsToLandlord();

        return parent::getEloquentQuery()->withoutGlobalScopes();
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



