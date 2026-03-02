<?php

namespace App\Filament\Resources\Invitations;

use App\Filament\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\Resources\Invitations\Pages\EditInvitation;
use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Filament\Resources\Invitations\Pages\ViewInvitation;
use App\Filament\Resources\Invitations\Schemas\InvitationForm;
use App\Filament\Resources\Invitations\Schemas\InvitationInfolist;
use App\Filament\Resources\Invitations\Tables\InvitationsTable;
use App\Models\Invitation;
use App\Support\AccessMatrix;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;
    protected static ?string $slug = 'invitations';
    protected static string|\UnitEnum|null $navigationGroup = 'Structure';
    protected static ?string $navigationLabel = 'Invitations Sent';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    public static function shouldRegisterNavigation(): bool
    {
        return AccessMatrix::allowedInviteRoles(auth()->user()) !== [];
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['org_admin', 'manufacturer', 'distributor', 'vendor'], true);
    }

    public static function canCreate(): bool
    {
        return AccessMatrix::allowedInviteRoles(auth()->user()) !== [];
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        if (AccessMatrix::isSuper($user)) {
            return $query;
        }

        return $query
            ->where('organization_id', $user->organization_id)
            ->where('inviter_id', $user->id);
    }

    public static function form(Schema $schema): Schema
    {
        return InvitationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvitationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvitationsTable::configure($table);
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
            'index' => ListInvitations::route('/'),
            'create' => CreateInvitation::route('/create'),
            'view' => ViewInvitation::route('/{record}'),
            'edit' => EditInvitation::route('/{record}/edit'),
        ];
    }
}




