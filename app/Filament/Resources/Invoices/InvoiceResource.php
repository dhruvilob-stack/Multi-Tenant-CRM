<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Filament\Resources\Invoices\Schemas\InvoiceInfolist;
use App\Filament\Resources\Invoices\Tables\InvoicesTable;
use App\Models\Invoice;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $slug = 'invoices';
    protected static ?string $recordTitleAttribute = 'invoice_number';
    protected static string|\UnitEnum|null $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Invoices';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth('tenant')->user()?->role, ['org_admin', 'manufacturer', 'distributor', 'vendor', 'consumer'], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth('tenant')->user()?->role, ['org_admin', 'manufacturer', 'distributor', 'vendor', 'consumer'], true);
    }

    public static function canCreate(): bool
    {
        return in_array(auth('tenant')->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::VENDOR], true);
    }

    public static function canEdit($record): bool
    {
        $user = auth('tenant')->user();
        if (AccessMatrix::isSuper($user) || $user?->role === UserRole::ORG_ADMIN) {
            return true;
        }

        return in_array($user?->role, [UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR], true);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth('tenant')->user();
        if (! $user || AccessMatrix::isSuper($user)) {
            return $query;
        }
        if ($user->role === UserRole::ORG_ADMIN) {
            return $query->where(function (Builder $invoiceQuery) use ($user): void {
                $invoiceQuery
                    ->whereHas('quotation.vendor', fn (Builder $q) => $q->where('organization_id', $user->organization_id))
                    ->orWhereHas('order.vendor', fn (Builder $q) => $q->where('organization_id', $user->organization_id));
            });
        }
        if ($user->role === UserRole::DISTRIBUTOR) {
            return $query->where(function (Builder $invoiceQuery) use ($user): void {
                $invoiceQuery
                    ->whereHas('quotation', fn (Builder $q) => $q->where('distributor_id', $user->id))
                    ->orWhereHas('order', fn (Builder $q) => $q->where('consumer_id', $user->id));
            });
        }
        if ($user->role === UserRole::VENDOR) {
            return $query->where(function (Builder $invoiceQuery) use ($user): void {
                $invoiceQuery
                    ->whereHas('quotation', fn (Builder $q) => $q->where('vendor_id', $user->id))
                    ->orWhereHas('order', fn (Builder $q) => $q->where('vendor_id', $user->id));
            });
        }
        if ($user->role === UserRole::CONSUMER) {
            return $query->whereHas('order', fn (Builder $q) => $q->where('consumer_id', $user->id));
        }
        if ($user->role === UserRole::MANUFACTURER) {
            return $query->where(function (Builder $invoiceQuery) use ($user): void {
                $invoiceQuery
                    ->whereHas('quotation.vendor', fn (Builder $q) => $q->where('organization_id', $user->organization_id))
                    ->orWhereHas('order.vendor', fn (Builder $q) => $q->where('organization_id', $user->organization_id));
            });
        }

        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
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
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}



