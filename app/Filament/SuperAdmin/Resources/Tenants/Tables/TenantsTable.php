<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Tables;

use App\Models\Organization;
use Filament\Actions\Action;
use App\Services\TenantLifecycleService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sr_no')
                    ->label('S.No')
                    ->rowIndex(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email'),
                TextColumn::make('status')->badge(),
                TextColumn::make('direct_users_count')
                    ->counts('directUsers')
                    ->label('Users'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete tenant')
                    ->modalDescription('Deletes tenant data, emails a backup, and removes the organization entry.')
                    ->action(function (Organization $record): void {
                        app(TenantLifecycleService::class)->deleteOrganizationTenant($record);

                        Notification::make()
                            ->success()
                            ->title('Tenant deleted')
                            ->body('Backup emailed and tenant database removed.')
                            ->send();
                    }),
                Action::make('openTenantPanel')
                    ->label('Open Tenant Panel')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('success')
                    ->url(fn (Organization $record): string => route('super-admin.tenants.open-admin', ['organization' => $record->id]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->action(function (Collection $records): void {
                        foreach ($records as $record) {
                            app(TenantLifecycleService::class)->deleteOrganizationTenant($record);
                        }

                        Notification::make()
                            ->success()
                            ->title('Tenants deleted')
                            ->body('Backups emailed and tenant databases removed.')
                            ->send();
                    }),
                ]),
            ]);
    }
}
