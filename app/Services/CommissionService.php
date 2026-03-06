<?php

namespace App\Services;

use App\Models\CommissionLedger;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MarginCommission;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Database\Eloquent\Builder;

class CommissionService
{
    /**
     * @var array<int, array{from:string,to:string}>
     */
    private array $steps = [
        ['from' => UserRole::MANUFACTURER, 'to' => UserRole::DISTRIBUTOR],
        ['from' => UserRole::DISTRIBUTOR, 'to' => UserRole::VENDOR],
        ['from' => UserRole::VENDOR, 'to' => UserRole::CONSUMER],
    ];

    public function generateForInvoice(Invoice $invoice): void
    {
        if (! $invoice->order_id) {
            return;
        }

        if (! in_array((string) $invoice->status, ['approved', 'paid'], true)) {
            return;
        }

        $invoice->loadMissing('items.product.manufacturer', 'quotation.vendor', 'quotation.distributor', 'order.consumer', 'order.vendor');

        $previousUserIds = CommissionLedger::query()
            ->where('invoice_id', $invoice->id)
            ->whereNotNull('from_user_id')
            ->pluck('from_user_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        CommissionLedger::query()->where('invoice_id', $invoice->id)->delete();

        $itemTotalsSum = (float) $invoice->items->sum(fn (InvoiceItem $item): float => (float) $item->total);
        $billedTotal = (float) ($invoice->order?->total_amount_billed ?? $invoice->grand_total ?? 0);
        $basisMultiplier = $itemTotalsSum > 0 ? ($billedTotal / $itemTotalsSum) : 1.0;

        $aggregates = [];

        foreach ($this->steps as $step) {
            $key = $step['from'].'>'.$step['to'];
            $aggregates[$key] = [
                'from_role' => $step['from'],
                'to_role' => $step['to'],
                'basis_amount' => 0.0,
                'commission_amount' => 0.0,
                'weighted_percentage_sum' => 0.0,
                'percentage_basis_sum' => 0.0,
                'has_percentage' => false,
                'from_user_id' => null,
                'to_user_id' => null,
            ];
        }

        foreach ($invoice->items as $item) {
            foreach ($this->steps as $step) {
                $key = $step['from'].'>'.$step['to'];
                $row = &$aggregates[$key];

                $rule = $this->resolveRuleForItem($item, $step['from'], $step['to']);
                $commissionType = (string) ($rule?->commission_type ?? 'percentage');
                $commissionRate = (float) ($rule?->commission_value ?? 0);
                $basisAmount = (float) $item->total * $basisMultiplier;
                $commissionAmount = $commissionType === 'fixed'
                    ? $commissionRate
                    : ($basisAmount * $commissionRate / 100);

                $row['basis_amount'] += $basisAmount;
                $row['commission_amount'] += $commissionAmount;

                if ($commissionType === 'percentage') {
                    $row['has_percentage'] = true;
                    $row['weighted_percentage_sum'] += ($commissionRate * $basisAmount);
                    $row['percentage_basis_sum'] += $basisAmount;
                }

                [$fromUserId, $toUserId] = $this->resolveUsers($invoice, $item, $step['from'], $step['to']);
                $row['from_user_id'] = $row['from_user_id'] ?? $fromUserId;
                $row['to_user_id'] = $row['to_user_id'] ?? $toUserId;

                unset($row);
            }
        }

        foreach ($aggregates as $row) {
            if ((float) $row['basis_amount'] <= 0) {
                continue;
            }

            $commissionType = $row['has_percentage'] ? 'percentage' : 'fixed';
            $commissionRate = $commissionType === 'percentage' && (float) $row['percentage_basis_sum'] > 0
                ? round(((float) $row['weighted_percentage_sum']) / (float) $row['percentage_basis_sum'], 4)
                : 0.0;

            CommissionLedger::query()->create([
                'invoice_id' => $invoice->id,
                'invoice_item_id' => null,
                'product_id' => null,
                'from_user_id' => $row['from_user_id'],
                'to_user_id' => $row['to_user_id'],
                'from_role' => $row['from_role'],
                'to_role' => $row['to_role'],
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'basis_amount' => round((float) $row['basis_amount'], 2),
                'commission_amount' => round((float) $row['commission_amount'], 2),
                'paid_amount' => 0,
                'status' => 'accrued',
            ]);
        }

        app(PartnerWalletService::class)->syncForUsers(
            collect($aggregates)
                ->pluck('from_user_id')
                ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
                ->map(fn ($id): int => (int) $id)
                ->merge($previousUserIds)
                ->values()
        );
    }

    public function clearForInvoice(Invoice $invoice): void
    {
        $fromUserIds = CommissionLedger::query()
            ->where('invoice_id', $invoice->id)
            ->whereNotNull('from_user_id')
            ->pluck('from_user_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        CommissionLedger::query()->where('invoice_id', $invoice->id)->delete();

        if ($fromUserIds !== []) {
            app(PartnerWalletService::class)->syncForUsers($fromUserIds);
        }
    }

    public function syncForInvoice(Invoice $invoice): void
    {
        if ($invoice->order_id && in_array((string) $invoice->status, ['approved', 'paid'], true)) {
            $this->generateForInvoice($invoice);
            return;
        }

        $this->clearForInvoice($invoice);
    }

    private function resolveRuleForItem(InvoiceItem $item, string $fromRole, string $toRole): ?MarginCommission
    {
        $organizationId = (int) ($item->invoice?->quotation?->vendor?->organization_id ?? $item->invoice?->order?->vendor?->organization_id ?? 0);

        return MarginCommission::query()
            ->where('from_role', $fromRole)
            ->where('to_role', $toRole)
            ->where('is_active', true)
            ->when($organizationId > 0, fn (Builder $query): Builder => $query->where('organization_id', $organizationId))
            ->where(function ($query) use ($item): void {
                $query->orWhere(function ($q) use ($item): void {
                    $q->where('rule_type', 'product')
                        ->where('product_id', $item->product_id);
                })->orWhere(function ($q) use ($item): void {
                    $q->where('rule_type', 'category')
                        ->where(function ($categoryQuery) use ($item): void {
                            $categoryQuery
                                ->whereNull('category_id')
                                ->orWhere('category_id', $item->product?->category_id);
                        });
                })->orWhere(function ($q): void {
                    $q->where('rule_type', 'global');
                });
            })
            ->orderByRaw("case when rule_type = 'product' then 1 when rule_type = 'category' and category_id is not null then 2 when rule_type = 'category' then 3 else 4 end")
            ->orderBy('priority')
            ->orderByDesc('id')
            ->first();
    }

    private function resolveUsers(Invoice $invoice, InvoiceItem $item, string $fromRole, string $toRole): array
    {
        $manufacturerId = $item->product?->manufacturer?->id;
        $vendorId = $invoice->quotation?->vendor_id ?: $invoice->order?->vendor_id;
        $consumerId = $invoice->order?->consumer_id;
        $distributorId = $invoice->quotation?->distributor_id ?: $this->resolveDistributorId($invoice, $vendorId);

        $map = [
            UserRole::MANUFACTURER => $manufacturerId,
            UserRole::DISTRIBUTOR => $distributorId,
            UserRole::VENDOR => $vendorId,
            UserRole::CONSUMER => $consumerId,
        ];

        return [
            $this->normalizeUserId($map[$fromRole] ?? null),
            $this->normalizeUserId($map[$toRole] ?? null),
        ];
    }

    private function normalizeUserId(mixed $userId): ?int
    {
        if ($userId === null) {
            return null;
        }

        return User::withoutGlobalScopes()->whereKey($userId)->exists() ? (int) $userId : null;
    }

    private function resolveDistributorId(Invoice $invoice, mixed $vendorId): ?int
    {
        $vendorId = is_numeric($vendorId) ? (int) $vendorId : null;
        if (! $vendorId) {
            return null;
        }

        /** @var User|null $vendor */
        $vendor = User::withoutGlobalScopes()->find($vendorId);
        if (! $vendor) {
            return null;
        }

        $parent = User::withoutGlobalScopes()->find($vendor->parent_id);
        if ($parent && $parent->role === UserRole::DISTRIBUTOR) {
            return (int) $parent->id;
        }

        $orgId = (int) ($invoice->quotation?->vendor?->organization_id ?? $invoice->order?->vendor?->organization_id ?? $vendor->organization_id ?? 0);
        if ($orgId <= 0) {
            return null;
        }

        return User::withoutGlobalScopes()
            ->where('role', UserRole::DISTRIBUTOR)
            ->where('organization_id', $orgId)
            ->orderBy('id')
            ->value('id');
    }
}
