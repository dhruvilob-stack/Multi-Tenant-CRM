<?php

namespace App\Filament\Resources\Consumers\Pages;

use App\Filament\Resources\Users\Concerns\StoresPanelLoginPrefillPassword;
use App\Filament\Resources\Consumers\ConsumerResource;
use App\Models\User;
use App\Support\OrganizationEmailFormatter;
use App\Services\UserAccessMailService;
use App\Support\UserRole;
use Filament\Resources\Pages\CreateRecord;

class CreateConsumer extends CreateRecord
{
    use StoresPanelLoginPrefillPassword;

    protected static string $resource = ConsumerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->capturePanelPrefillPassword($data);
        $user = auth('tenant')->user();

        $data['role'] = UserRole::CONSUMER;
        $data['status'] = $data['status'] ?? 'active';

        if ($user && $user->role !== UserRole::SUPER_ADMIN) {
            $data['organization_id'] = $user->organization_id;
        }

        $data['parent_id'] = $data['parent_id'] ?? $user?->id;
        $data['email'] = $this->ensureSystemEmail($data);
        [$data['first_name'], $data['last_name']] = $this->splitAdminName($data);
        unset($data['admin_first_name'], $data['admin_last_name']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record instanceof User) {
            $this->savePanelPrefillPassword($this->record);
            $password = $this->getPanelPrefillPassword();
            if ($password) {
                app(UserAccessMailService::class)->sendForUser($this->record, $password);
            }
        }
    }

    private function ensureSystemEmail(array $data): string
    {
        $name = (string) ($data['name'] ?? '');
        $role = UserRole::CONSUMER;
        $org = auth('tenant')->user()?->organization;
        $domainSource = $org?->email ?: ($org?->name ? $org->name.'.com' : 'example.com');

        return OrganizationEmailFormatter::suggestEmail($name !== '' ? $name : 'user', $role, $domainSource);
    }

    private function splitAdminName(array $data): array
    {
        $first = (string) ($data['admin_first_name'] ?? '');
        $last = (string) ($data['admin_last_name'] ?? '');

        return [$first, $last];
    }
}
