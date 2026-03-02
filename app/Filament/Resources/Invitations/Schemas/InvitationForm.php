<?php

namespace App\Filament\Resources\Invitations\Schemas;

use App\Support\AccessMatrix;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Send Invitation')
                    ->description('Invite users to join your organization. You can only invite roles below your level.')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Hidden::make('inviter_id')
                            ->default(fn() => auth()->id()),
                        Hidden::make('organization_id')
                            ->default(fn() => auth()->user()?->organization_id),
                        Grid::make(2)->schema([
                            Placeholder::make('organization_name')
                                ->label('Organization')
                                ->content(fn() => auth()->user()?->organization?->name ?? 'N/A'),
                            Placeholder::make('inviter_name')
                                ->label('Inviter')
                                ->content(fn() => sprintf(
                                    '%s (%s)',
                                    auth()->user()?->name ?? 'N/A',
                                    auth()->user()?->email ?? 'N/A'
                                )),
                        ]),
                        TextInput::make('invitee_email')
                            ->label('Invitee Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('name@example.com')
                            ->helperText('A join link will be sent to this exact email address.'),
                        Select::make('role')
                            ->label('Invite as Role')
                            ->options(fn() => AccessMatrix::allowedInviteRoles(auth()->user()))
                            ->required()
                            ->native(false)
                            ->helperText('Only allowed downward roles are listed.'),
                    ]),
            ]);
    }
}
