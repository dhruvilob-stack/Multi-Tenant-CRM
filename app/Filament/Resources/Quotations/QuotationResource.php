<?php

namespace App\Filament\Resources\Quotations;

use App\Filament\Resources\Quotations\Pages\CreateQuotation;
use App\Filament\Resources\Quotations\Pages\EditQuotation;
use App\Filament\Resources\Quotations\Pages\ListQuotations;
use App\Filament\Resources\Quotations\Pages\ViewQuotation;
use App\Filament\Resources\Quotations\Schemas\QuotationForm;
use App\Filament\Resources\Quotations\Schemas\QuotationInfolist;
use App\Filament\Resources\Quotations\Tables\QuotationsTable;
use App\Models\Quotation;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;
    protected static ?string $slug = 'quotations';
    protected static ?string $recordTitleAttribute = 'quotation_number';
    protected static string|\UnitEnum|null $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Quotations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth('tenant')->user()?->role, ['org_admin', 'distributor', 'vendor'], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth('tenant')->user()?->role, ['org_admin', 'distributor', 'vendor'], true);
    }

    public static function canCreate(): bool
    {
        return in_array(auth('tenant')->user()?->role, [UserRole::SUPER_ADMIN, UserRole::VENDOR], true);
    }

    public static function canEdit($record): bool
    {
        $user = auth('tenant')->user();
        if (AccessMatrix::isSuper($user) || $user?->role === UserRole::ORG_ADMIN) {
            return true;
        }
        if ($user?->role === UserRole::VENDOR) {
            return (int) $record->vendor_id === (int) $user->id;
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth('tenant')->user();
        if (! $user || AccessMatrix::isSuper($user)) {
            return $query;
        }
        if ($user->role === UserRole::ORG_ADMIN) {
            return $query->whereHas('vendor', fn (Builder $q) => $q->where('organization_id', $user->organization_id));
        }
        if ($user->role === UserRole::VENDOR) {
            return $query->where('vendor_id', $user->id);
        }
        if ($user->role === UserRole::DISTRIBUTOR) {
            return $query->where('distributor_id', $user->id);
        }
        if ($user->role === UserRole::MANUFACTURER) {
            return $query->whereHas('vendor', fn (Builder $q) => $q->where('organization_id', $user->organization_id));
        }

        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return QuotationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuotationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotationsTable::configure($table);
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
            'index' => ListQuotations::route('/'),
            'create' => CreateQuotation::route('/create'),
            'view' => ViewQuotation::route('/{record}'),
            'edit' => EditQuotation::route('/{record}/edit'),
        ];
    }
}



