<?php

namespace App\Filament\Resources\Organizations;

use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Resources\Organizations\Schemas\OrganizationInfolist;
use App\Filament\Resources\Organizations\Tables\OrganizationsTable;
use App\Models\Organization;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;
    protected static ?string $slug = 'organizations';
    protected static string|\UnitEnum|null $navigationGroup = 'Structure';
    protected static ?string $navigationLabel = 'My Organization';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    public static function shouldRegisterNavigation(): bool
    {
        return auth('tenant')->user()?->role === UserRole::SUPER_ADMIN;
    }

    public static function canViewAny(): bool
    {
        return in_array(auth('tenant')->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::MANUFACTURER], true);
    }

    public static function canCreate(): bool
    {
        return in_array(auth('tenant')->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true);
    }

    public static function canEdit($record): bool
    {
        $user = auth('tenant')->user();
        if (AccessMatrix::isSuper($user)) {
            return true;
        }

        return $user?->role === UserRole::ORG_ADMIN && (int) $record->id === (int) $user->organization_id;
    }

    public static function canDelete($record): bool
    {
        return AccessMatrix::isSuper(auth('tenant')->user());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth('tenant')->user();

        if (! $user || AccessMatrix::isSuper($user)) {
            return $query;
        }

        return $query->whereKey($user->organization_id);
    }

    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrganizationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
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
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'view' => ViewOrganization::route('/{record}'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }
}



