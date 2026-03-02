<?php

namespace App\Filament\SuperAdmin\Resources\CustomRoles;

use App\Filament\SuperAdmin\Resources\CustomRoles\Pages\CreateCustomRole;
use App\Filament\SuperAdmin\Resources\CustomRoles\Pages\EditCustomRole;
use App\Filament\SuperAdmin\Resources\CustomRoles\Pages\ListCustomRoles;
use App\Filament\SuperAdmin\Resources\CustomRoles\Pages\ViewCustomRole;
use App\Filament\SuperAdmin\Resources\CustomRoles\Schemas\CustomRoleForm;
use App\Filament\SuperAdmin\Resources\CustomRoles\Schemas\CustomRoleInfolist;
use App\Filament\SuperAdmin\Resources\CustomRoles\Tables\CustomRolesTable;
use App\Models\CustomRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomRoleResource extends Resource
{
    protected static ?string $model = CustomRole::class;
    protected static ?string $slug = 'roles-permissions';
    protected static ?string $recordTitleAttribute = 'name';
    protected static string|\UnitEnum|null $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Roles & Permissions';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function form(Schema $schema): Schema
    {
        return CustomRoleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomRoleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomRolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomRoles::route('/'),
            'create' => CreateCustomRole::route('/create'),
            'view' => ViewCustomRole::route('/{record}'),
            'edit' => EditCustomRole::route('/{record}/edit'),
        ];
    }
}

