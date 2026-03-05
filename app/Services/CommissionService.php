<?php

namespace App\Services;

use App\Models\CommissionLedger;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MarginCommission;
use App\Models\User;
use App\Support\UserRole;

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

        $invoice->loadMissing('items.product.manufacturer', 'quotation.vendor', 'quotation.distributor', 'order.consumer');

        CommissionLedger::query()->where('invoice_id', $invoice->id)->delete();

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
                $basisAmount = (float) $item->total;
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
                ->values()
        );
    }

    private function resolveRuleForItem(InvoiceItem $item, string $fromRole, string $toRole): ?MarginCommission
    {
        return MarginCommission::query()
            ->where('from_role', $fromRole)
            ->where('to_role', $toRole)
            ->where('is_active', true)
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
        $distributorId = $invoice->quotation?->distributor_id;
        $vendorId = $invoice->quotation?->vendor_id;
        $consumerId = $invoice->order?->consumer_id;

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

        return User::query()->whereKey($userId)->exists() ? (int) $userId : null;
    }
}
