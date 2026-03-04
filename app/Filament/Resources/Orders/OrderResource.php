<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Filament\Resources\Orders\Widgets\OrderStats;
use App\Models\Order;
use App\Support\AccessMatrix;
use App\Support\UserRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $slug = 'orders';

    protected static ?string $recordTitleAttribute = 'order_number';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Orders';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(auth()->user()?->role, ['org_admin', 'manufacturer', 'distributor', 'vendor', 'consumer'], true);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['org_admin', 'manufacturer', 'distributor', 'vendor', 'consumer'], true);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::VENDOR, UserRole::CONSUMER], true);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (AccessMatrix::isSuper($user) || $user?->role === UserRole::ORG_ADMIN) {
            return true;
        }
        if ($user?->role === UserRole::VENDOR) {
            return (int) $record->vendor_id === (int) $user->id;
        }
        if ($user?->role === UserRole::CONSUMER) {
            return (int) $record->consumer_id === (int) $user->id;
        }

        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // PaymentsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            OrderStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user || AccessMatrix::isSuper($user)) {
            return $query;
        }
        if ($user->role === UserRole::ORG_ADMIN) {
            return $query->whereHas('vendor', fn(Builder $q) => $q->where('organization_id', $user->organization_id));
        }
        if ($user->role === UserRole::VENDOR) {
            return $query->where('vendor_id', $user->id);
        }
        if ($user->role === UserRole::CONSUMER) {
            return $query->where('consumer_id', $user->id);
        }
        if (in_array($user->role, [UserRole::MANUFACTURER, UserRole::DISTRIBUTOR], true)) {
            return $query->whereHas('vendor', fn(Builder $q) => $q->where('organization_id', $user->organization_id));
        }

        return $query->whereRaw('1=0');
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
        $modelClass = static::$model;

        return (string) $modelClass::whereIn('status', ['new', 'processing'])->count();
    }
}
