<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Pages;

use App\Filament\SuperAdmin\Resources\Tenants\TenantResource;
use App\Models\User;
use App\Services\TenantLifecycleService;
use App\Support\UserRole;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['domain'] = $this->record->tenant?->domain;
        $data['slug'] = $this->record->tenant?->slug ?: $this->record->slug;
        $data['slug_preview'] = $data['slug'];
        $data['admin_contact_email'] = User::query()
            ->where('organization_id', (int) $this->record->id)
            ->where('role', UserRole::ORG_ADMIN)
            ->value('contact_email');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Tenant')
                ->modalDescription('This will export the tenant DB backup, email it, and permanently remove tenant DB/data.')
                ->action(function (): void {
                    app(TenantLifecycleService::class)->deleteOrganizationTenant($this->record);

                    Notification::make()
                        ->success()
                        ->title('Tenant deleted')
                        ->body('Backup emailed and tenant database removed.')
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $tenantSlug = Str::slug((string) ($data['slug'] ?? ''));
        $tenantDomain = strtolower(trim((string) ($data['domain'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $adminContactEmail = (string) ($data['admin_contact_email'] ?? '');
        unset($data['password'], $data['password_confirmation'], $data['slug_preview']);

        $record->forceFill([
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'slug' => $tenantSlug !== '' ? $tenantSlug : (string) $record->slug,
            'status' => (string) ($data['status'] ?? $record->status),
        ])->save();

        User::query()
            ->where('organization_id', (int) $record->id)
            ->where('role', UserRole::ORG_ADMIN)
            ->update([
                'email' => (string) $record->email,
                'updated_at' => now(),
            ]);

        User::query()
            ->where('organization_id', (int) $record->id)
            ->where('role', UserRole::ORG_ADMIN)
            ->update([
                'contact_email' => $adminContactEmail,
                'updated_at' => now(),
            ]);

        if ($password !== '') {
            $hashedPassword = Hash::make($password);

            User::query()
                ->where('organization_id', (int) $record->id)
                ->where('role', UserRole::ORG_ADMIN)
                ->update([
                    'password' => $hashedPassword,
                    'updated_at' => now(),
                ]);

            if ($record->tenant) {
                $record->tenant->forceFill([
                    'data' => array_merge((array) ($record->tenant->data ?? []), [
                        'login_email' => (string) $record->email,
                        'login_password_encrypted' => Crypt::encryptString($password),
                    ]),
                ])->save();
            }
        }

        if ($record->tenant_id) {
            app(TenantLifecycleService::class)->updateOrganizationTenant(
                $record,
                $tenantSlug !== '' ? $tenantSlug : null,
                $tenantDomain !== '' ? $tenantDomain : null,
            );

            if ($record->tenant) {
                app(\App\Services\TenantDatabaseManager::class)->activateTenantConnection($record->tenant);
                $tenantConnection = config('tenancy.tenant_connection', 'tenant');
                \Illuminate\Support\Facades\DB::connection($tenantConnection)
                    ->table('users')
                    ->where('organization_id', (int) $record->id)
                    ->where('role', UserRole::ORG_ADMIN)
                    ->update([
                        'email' => (string) $record->email,
                        'contact_email' => $adminContactEmail,
                        'updated_at' => now(),
                    ]);
                app(\App\Services\TenantDatabaseManager::class)->activateLandlordConnection();
            }
        }

        return $record;
    }
}
