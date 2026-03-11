<?php

namespace App\Filament\Pages;

use App\Models\OrganizationSubscription;
use App\Models\OrganizationSubscriptionInvoice;
use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Subscription extends Page
{
    protected string $view = 'filament.pages.subscription';

    protected static ?string $slug = 'subscription';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;
    protected static ?string $navigationLabel = 'Subscription';
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    public ?OrganizationSubscription $subscription = null;

    /** @var array<int, OrganizationSubscriptionInvoice> */
    public array $invoices = [];

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || $user->role !== UserRole::ORG_ADMIN) {
            return;
        }

        $organization = $user->organization;
        if (! $organization) {
            return;
        }

        $this->subscription = $organization->latestSubscription()->first();
        $this->invoices = OrganizationSubscriptionInvoice::query()
            ->where('organization_id', $organization->id)
            ->orderByDesc('issued_at')
            ->limit(10)
            ->get()
            ->all();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === UserRole::ORG_ADMIN;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::ORG_ADMIN;
    }
}
