<?php

namespace App\Filament\Resources\ProductChangeRequests;

use App\Filament\Resources\ProductChangeRequests\Pages\CreateProductChangeRequest;
use App\Filament\Resources\ProductChangeRequests\Pages\EditProductChangeRequest;
use App\Filament\Resources\ProductChangeRequests\Pages\ListProductChangeRequests;
use App\Filament\Resources\ProductChangeRequests\Pages\ViewProductChangeRequest;
use App\Filament\Resources\ProductChangeRequests\Schemas\ProductChangeRequestForm;
use App\Filament\Resources\ProductChangeRequests\Schemas\ProductChangeRequestInfolist;
use App\Filament\Resources\ProductChangeRequests\Tables\ProductChangeRequestsTable;
use App\Models\ProductChangeRequest;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductChangeRequestResource extends Resource
{
    protected static ?string $model = ProductChangeRequest::class;
    protected static ?string $slug = 'products/change-requests';
    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';
    protected static ?string $navigationLabel = 'Product Change Requests';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::ORG_ADMIN, UserRole::MANUFACTURER], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::MANUFACTURER], true);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === UserRole::MANUFACTURER;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->role === UserRole::ORG_ADMIN || AccessMatrix::isSuper(auth()->user());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || AccessMatrix::isSuper($user)) {
            return $query;
        }

        if ($user->role === UserRole::ORG_ADMIN) {
            return $query->where('organization_id', $user->organization_id);
        }

        if ($user->role === UserRole::MANUFACTURER) {
            return $query->where('manufacturer_id', $user->id);
        }

        return $query->whereRaw('1=0');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductChangeRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductChangeRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductChangeRequestsTable::configure($table);
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
            'index' => ListProductChangeRequests::route('/'),
            'create' => CreateProductChangeRequest::route('/create'),
            'view' => ViewProductChangeRequest::route('/{record}'),
            'edit' => EditProductChangeRequest::route('/{record}/edit'),
        ];
    }
}
