<?php

namespace App\Filament\Resources\Invitations;

use App\Filament\Clusters\Mail;
use App\Filament\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\Resources\Invitations\Pages\EditInvitation;
use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Filament\Resources\Invitations\Pages\ViewInvitation;
use App\Filament\Resources\Invitations\Schemas\InvitationForm;
use App\Filament\Resources\Invitations\Schemas\InvitationInfolist;
use App\Filament\Resources\Invitations\Tables\InvitationsTable;
use App\Models\Invitation;
use App\Support\AccessMatrix;
use App\Support\UserRole;
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
    protected static ?string $cluster = Mail::class;
    protected static ?string $navigationLabel = 'Invitations Sent';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    public static function shouldRegisterNavigation(): bool
    {
        return AccessMatrix::allowedInviteRoles(auth('tenant')->user()) !== [];
    }

    public static function canViewAny(): bool
    {
        return in_array(auth('tenant')->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN, UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR], true);
    }

    public static function canCreate(): bool
    {
        return AccessMatrix::allowedInviteRoles(auth('tenant')->user()) !== [];
    }

    public static function canEdit($record): bool
    {
        $user = auth('tenant')->user();

        if (! $user) {
            return false;
        }

        if (AccessMatrix::isSuper($user)) {
            return ! $record->isAccepted();
        }

        if ((int) $record->organization_id !== (int) $user->organization_id) {
            return false;
        }

        if ($user->role === UserRole::ORG_ADMIN) {
            return ! $record->isAccepted();
        }

        return (int) $record->inviter_id === (int) $user->id && ! $record->isAccepted();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth('tenant')->user();

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        if (AccessMatrix::isSuper($user)) {
            return $query;
        }

        if ($user->role === UserRole::ORG_ADMIN) {
            return $query->where('organization_id', $user->organization_id);
        }

        return $query->where('inviter_id', $user->id);
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


