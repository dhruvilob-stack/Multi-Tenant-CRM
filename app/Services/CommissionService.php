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
    public function generateForInvoice(Invoice $invoice): void
    {
        $invoice->loadMissing('items.product.manufacturer', 'quotation.vendor', 'quotation.distributor', 'order.consumer');

        CommissionLedger::query()->where('invoice_id', $invoice->id)->delete();

        foreach ($invoice->items as $item) {
            $this->createEntryForStep($invoice, $item, UserRole::MANUFACTURER, UserRole::DISTRIBUTOR);
            $this->createEntryForStep($invoice, $item, UserRole::DISTRIBUTOR, UserRole::VENDOR);
            $this->createEntryForStep($invoice, $item, UserRole::VENDOR, UserRole::CONSUMER);
        }
    }

    private function createEntryForStep(Invoice $invoice, InvoiceItem $item, string $fromRole, string $toRole): void
    {
        $rule = MarginCommission::query()
            ->where('from_role', $fromRole)
            ->where('to_role', $toRole)
            ->where(function ($query) use ($item): void {
                $query
                    ->where('product_id', $item->product_id)
                    ->orWhere(function ($q) use ($item): void {
                        $q->whereNull('product_id')
                            ->where('category_id', $item->product?->category_id);
                    })
                    ->orWhere(function ($q): void {
                        $q->whereNull('product_id')->whereNull('category_id');
                    });
            })
            ->orderByRaw('product_id is null')
            ->orderByRaw('category_id is null')
            ->first();

        $commissionType = $rule?->commission_type ?? 'percentage';
        $commissionRate = (float) ($rule?->commission_value ?? 0);
        $basisAmount = match ($fromRole) {
            UserRole::MANUFACTURER => (float) (($item->product?->base_price ?? 0) * (float) $item->qty),
            default => (float) $item->total,
        };
        $commissionAmount = $commissionType === 'fixed'
            ? $commissionRate
            : ($basisAmount * $commissionRate / 100);

        [$fromUserId, $toUserId] = $this->resolveUsers($invoice, $item, $fromRole, $toRole);

        CommissionLedger::query()->create([
            'invoice_id' => $invoice->id,
            'invoice_item_id' => $item->id,
            'product_id' => $item->product_id,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'from_role' => $fromRole,
            'to_role' => $toRole,
            'commission_type' => $commissionType,
            'commission_rate' => $commissionRate,
            'basis_amount' => $basisAmount,
            'commission_amount' => $commissionAmount,
            'status' => 'accrued',
        ]);
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
