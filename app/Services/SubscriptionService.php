<?php

namespace App\Services;

use App\Mail\SubscriptionInvoiceMail;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationSubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function getActiveSubscription(Organization $organization): ?OrganizationSubscription
    {
        $subscription = $organization->latestSubscription()->first();

        if (! $subscription) {
            return null;
        }

        if ($subscription->status === 'active' && $subscription->ends_at && $subscription->ends_at->isPast()) {
            $subscription->update(['status' => 'expired']);
        }

        return $subscription->status === 'active' ? $subscription : null;
    }

    /**
     * @param array<string, mixed> $billingDetails
     * @param array<string, mixed> $paymentMeta
     */
    public function activate(
        User $actor,
        string $planKey,
        array $billingDetails,
        string $paymentMethod,
        ?string $paymentReference = null,
        array $paymentMeta = [],
        ?string $currencyOverride = null
    ): OrganizationSubscription
    {
        $organization = $actor->organization;
        if (! $organization) {
            throw new \RuntimeException('Organization not found for subscription.');
        }

        $plan = app(PlanCatalogService::class)->find($planKey);
        if (! $plan) {
            throw new \RuntimeException('Selected plan not found.');
        }

        $settings = app(PlanCatalogService::class)->settings();
        $taxRate = (float) ($settings['tax_rate'] ?? 0);
        $platformFee = (float) ($settings['platform_fee'] ?? 0);
        $currency = $currencyOverride !== null && $currencyOverride !== ''
            ? $currencyOverride
            : (string) ($settings['currency'] ?? 'USD');

        $planPrice = (float) ($plan['price'] ?? 0);
        $taxAmount = round($planPrice * $taxRate, 2);
        $total = round($planPrice + $taxAmount + $platformFee, 2);

        $billingCycle = (string) ($plan['billing_cycle'] ?? 'month');
        $startsAt = now();
        $endsAt = $this->calculateEndsAt($startsAt, $billingCycle);

        $createdInvoice = null;
        $subscription = DB::transaction(function () use (
            $organization,
            $actor,
            $plan,
            $planKey,
            $currency,
            $billingCycle,
            $planPrice,
            $taxAmount,
            $platformFee,
            $total,
            $startsAt,
            $endsAt,
            $paymentMethod,
            $billingDetails,
            $paymentReference,
            $paymentMeta,
            &$createdInvoice
        ): OrganizationSubscription {
            OrganizationSubscription::query()
                ->where('organization_id', $organization->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            $subscription = OrganizationSubscription::query()->create([
                'organization_id' => $organization->id,
                'subscribed_by' => $actor->id,
                'plan_key' => $planKey,
                'plan_name' => (string) ($plan['name'] ?? $planKey),
                'currency' => $currency,
                'billing_cycle' => $billingCycle,
                'plan_price' => $planPrice,
                'tax_amount' => $taxAmount,
                'platform_fee' => $platformFee,
                'total_amount' => $total,
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'next_renew_at' => $endsAt,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference ?: $this->generatePaymentReference(),
                'billing_details' => $billingDetails,
                'plan_snapshot' => $plan,
                'payment_meta' => [
                    'activated_by' => $actor->id,
                    ...$paymentMeta,
                ],
            ]);

            $invoice = OrganizationSubscriptionInvoice::query()->create([
                'organization_id' => $organization->id,
                'subscription_id' => $subscription->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'currency' => $currency,
                'plan_price' => $planPrice,
                'tax_amount' => $taxAmount,
                'platform_fee' => $platformFee,
                'total_amount' => $total,
                'status' => 'paid',
                'issued_at' => now(),
                'payment_method' => $paymentMethod,
                'payment_reference' => $subscription->payment_reference,
            ]);

            try {
                $pdf = app(SubscriptionInvoicePdfService::class)->generate($invoice);
                $invoice->update(['pdf_path' => $pdf['path']]);
            } catch (\Throwable $e) {
                Log::warning('Subscription invoice PDF failed: '.$e->getMessage());
            }

            $createdInvoice = $invoice->fresh();

            return $subscription;
        });

        $this->activateOrganization($organization);
        $this->sendSubscriptionInvoiceMail($actor, $createdInvoice);

        return $subscription->fresh();
    }

    private function calculateEndsAt(\Illuminate\Support\Carbon $start, string $billingCycle): \Illuminate\Support\Carbon
    {
        return match ($billingCycle) {
            'year', 'annual', 'yearly' => $start->copy()->addYearNoOverflow(),
            default => $start->copy()->addMonthNoOverflow(),
        };
    }

    private function generateInvoiceNumber(): string
    {
        return 'SUB-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function generatePaymentReference(): string
    {
        return 'PAY-' . strtoupper(Str::random(10));
    }

    private function activateOrganization(Organization $organization): void
    {
        $organization->forceFill(['status' => 'active'])->save();

        User::query()
            ->where('organization_id', $organization->id)
            ->update(['status' => 'active']);

        $landlord = config('tenancy.landlord_connection', 'landlord');
        User::on($landlord)
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->update(['status' => 'active']);

        $tenant = $organization->tenant;
        if ($tenant instanceof Tenant) {
            $tenant->forceFill(['status' => 'active'])->save();
            app(TenantLifecycleService::class)->updateOrganizationTenant($organization);
        }
    }

    private function sendSubscriptionInvoiceMail(User $actor, ?OrganizationSubscriptionInvoice $invoice): void
    {
        if (! $invoice) {
            return;
        }

        $recipient = $actor->contact_email ?: $actor->email;
        if (! $recipient || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($recipient)->send(new SubscriptionInvoiceMail($invoice));
        } catch (\Throwable $e) {
            Log::warning('Subscription invoice mail failed: '.$e->getMessage());
        }
    }
}
