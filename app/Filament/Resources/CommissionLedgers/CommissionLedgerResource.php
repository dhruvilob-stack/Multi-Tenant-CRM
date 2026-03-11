<?php

namespace App\Filament\Resources\CommissionLedgers;

use App\Filament\Resources\CommissionLedgers\Pages\CreateCommissionLedger;
use App\Filament\Resources\CommissionLedgers\Pages\EditCommissionLedger;
use App\Filament\Resources\CommissionLedgers\Pages\ListCommissionLedgers;
use App\Filament\Resources\CommissionLedgers\Pages\ViewCommissionLedger;
use App\Filament\Resources\CommissionLedgers\Schemas\CommissionLedgerForm;
use App\Filament\Resources\CommissionLedgers\Schemas\CommissionLedgerInfolist;
use App\Filament\Resources\CommissionLedgers\Tables\CommissionLedgersTable;
use App\Models\CommissionLedger;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommissionLedgerResource extends Resource
{
    protected static ?string $model = CommissionLedger::class;
    protected static ?string $slug = 'commission-ledger';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Commission Ledger';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth('tenant')->user()?->role, ['super_admin', 'org_admin', 'manufacturer', 'distributor', 'vendor'], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth('tenant')->user()?->role, ['super_admin', 'org_admin', 'manufacturer', 'distributor', 'vendor'], true);
    }

    public static function canCreate(): bool
    {
        return auth('tenant')->user()?->role === UserRole::ORG_ADMIN;
    }

    public static function canEdit($record): bool
    {
        return auth('tenant')->user()?->role === UserRole::ORG_ADMIN;
    }

    public static function canDelete($record): bool
    {
        return auth('tenant')->user()?->role === UserRole::ORG_ADMIN;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth('tenant')->user();
        if (! $user || AccessMatrix::isSuper($user)) {
            return $query;
        }

        return $query->where(function (Builder $ledgerQuery) use ($user): void {
            $ledgerQuery
                ->whereHas('invoice.quotation.vendor', fn (Builder $q) => $q->where('organization_id', $user->organization_id))
                ->orWhereHas('invoice.order.vendor', fn (Builder $q) => $q->where('organization_id', $user->organization_id));
        });
    }

    public static function form(Schema $schema): Schema
    {
        return CommissionLedgerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommissionLedgerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommissionLedgersTable::configure($table);
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
            'index' => ListCommissionLedgers::route('/'),
            'create' => CreateCommissionLedger::route('/create'),
            'view' => ViewCommissionLedger::route('/{record}'),
            'edit' => EditCommissionLedger::route('/{record}/edit'),
        ];
    }
}


