<?php

namespace App\Filament\Resources\MarginCommissions;

use App\Filament\Resources\MarginCommissions\Pages\CreateMarginCommission;
use App\Filament\Resources\MarginCommissions\Pages\EditMarginCommission;
use App\Filament\Resources\MarginCommissions\Pages\ListMarginCommissions;
use App\Filament\Resources\MarginCommissions\Pages\ViewMarginCommission;
use App\Filament\Resources\MarginCommissions\Schemas\MarginCommissionForm;
use App\Filament\Resources\MarginCommissions\Schemas\MarginCommissionInfolist;
use App\Filament\Resources\MarginCommissions\Tables\MarginCommissionsTable;
use App\Models\MarginCommission;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarginCommissionResource extends Resource
{
    protected static ?string $model = MarginCommission::class;
    protected static ?string $slug = 'commissions';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Commission Rules';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, ['org_admin', 'manufacturer', 'distributor', 'vendor'], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['org_admin', 'manufacturer', 'distributor', 'vendor'], true);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === UserRole::ORG_ADMIN;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->role === UserRole::ORG_ADMIN;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->role === UserRole::ORG_ADMIN;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if (! $user || AccessMatrix::isSuper($user)) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($user): void {
            $scoped
                ->where('organization_id', $user->organization_id)
                ->orWhere(function (Builder $legacy) use ($user): void {
                    $legacy
                        ->whereNull('organization_id')
                        ->whereHas('product.manufacturer', fn (Builder $q) => $q->where('organization_id', $user->organization_id));
                });
        });
    }

    public static function form(Schema $schema): Schema
    {
        return MarginCommissionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MarginCommissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarginCommissionsTable::configure($table);
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
            'index' => ListMarginCommissions::route('/'),
            'create' => CreateMarginCommission::route('/create'),
            'view' => ViewMarginCommission::route('/{record}'),
            'edit' => EditMarginCommission::route('/{record}/edit'),
        ];
    }
}


