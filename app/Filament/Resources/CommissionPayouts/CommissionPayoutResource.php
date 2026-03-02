<?php

namespace App\Filament\Resources\CommissionPayouts;

use App\Filament\Resources\CommissionPayouts\Pages\CreateCommissionPayout;
use App\Filament\Resources\CommissionPayouts\Pages\EditCommissionPayout;
use App\Filament\Resources\CommissionPayouts\Pages\ListCommissionPayouts;
use App\Filament\Resources\CommissionPayouts\Pages\ViewCommissionPayout;
use App\Filament\Resources\CommissionPayouts\Schemas\CommissionPayoutForm;
use App\Filament\Resources\CommissionPayouts\Schemas\CommissionPayoutInfolist;
use App\Filament\Resources\CommissionPayouts\Tables\CommissionPayoutsTable;
use App\Models\CommissionPayout;
use App\Support\AccessMatrix;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommissionPayoutResource extends Resource
{
    protected static ?string $model = CommissionPayout::class;
    protected static ?string $slug = 'commission-payouts';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Payouts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

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

        return $query->whereHas('user', fn (Builder $q) => $q->where('organization_id', $user->organization_id));
    }

    public static function form(Schema $schema): Schema
    {
        return CommissionPayoutForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommissionPayoutInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommissionPayoutsTable::configure($table);
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
            'index' => ListCommissionPayouts::route('/'),
            'create' => CreateCommissionPayout::route('/create'),
            'view' => ViewCommissionPayout::route('/{record}'),
            'edit' => EditCommissionPayout::route('/{record}/edit'),
        ];
    }
}




