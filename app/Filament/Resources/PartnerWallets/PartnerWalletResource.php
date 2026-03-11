<?php

namespace App\Filament\Resources\PartnerWallets;

use App\Filament\Resources\PartnerWallets\Pages\ListPartnerWallets;
use App\Filament\Resources\PartnerWallets\Tables\PartnerWalletsTable;
use App\Models\PartnerWallet;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PartnerWalletResource extends Resource
{
    protected static ?string $model = PartnerWallet::class;

    protected static ?string $slug = 'wallets';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Wallets';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth('tenant')->user()?->role, [
            UserRole::SUPER_ADMIN,
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
            UserRole::VENDOR,
        ], true);
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('user:id,name,role,organization_id');
        $user = auth('tenant')->user();

        if (! $user || AccessMatrix::isSuper($user)) {
            return $query;
        }

        if ($user->role === UserRole::ORG_ADMIN) {
            return $query->where('organization_id', $user->organization_id);
        }

        if (in_array($user->role, [UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR], true)) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return PartnerWalletsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartnerWallets::route('/'),
        ];
    }
}
