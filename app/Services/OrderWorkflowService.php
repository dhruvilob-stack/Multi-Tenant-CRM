<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quotation;
use App\Models\Inventory;
use App\Models\User;
use App\Support\QuotationStatus;
use App\Support\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function markPaidAndGenerateInvoice(Order $order): Invoice
    {
        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'Cancelled orders cannot be paid.',
            ]);
        }

        if ((string) $order->payment_status !== 'confirmed') {
            throw ValidationException::withMessages([
                'payment_status' => 'Invoice can only be generated when payment status is confirmed.',
            ]);
        }

        if ($order->invoice_id) {
            throw ValidationException::withMessages([
                'invoice_id' => 'Invoice already exists for this order.',
            ]);
        }

        $order->loadMissing('vendor.organization', 'consumer', 'items.product', 'vendor.parent');

        if ($order->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one product item before payment.',
            ]);
        }

        $vendor = $order->vendor;
        if (! $vendor || $vendor->role !== UserRole::VENDOR) {
            throw ValidationException::withMessages([
                'vendor_id' => 'Order vendor is invalid.',
            ]);
        }

        $distributor = $vendor->parent;
        if (! $distributor || $distributor->role !== UserRole::DISTRIBUTOR) {
            $distributor = User::query()
                ->where('role', UserRole::DISTRIBUTOR)
                ->where('organization_id', $vendor->organization_id)
                ->orderBy('id')
                ->first();
        }

        if (! $distributor || $distributor->role !== UserRole::DISTRIBUTOR) {
            throw ValidationException::withMessages([
                'vendor_id' => 'No distributor found in this organization to create quotation/invoice.',
            ]);
        }

        $subtotal = (float) $order->items->sum(fn (OrderItem $item) => (float) $item->line_total);

        return DB::transaction(function () use ($order, $vendor, $distributor, $subtotal): Invoice {
            $quotation = Quotation::query()->create([
                'quotation_number' => $this->nextQuotationNumber(),
                'vendor_id' => $vendor->id,
                'distributor_id' => $distributor->id,
                'status' => QuotationStatus::DRAFT,
                'subject' => 'Order '.$order->order_number.' quotation',
                'valid_until' => now()->addDays(7)->toDateString(),
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => $subtotal,
                'notes' => 'Auto-generated from order '.$order->order_number,
            ]);

            foreach ($order->items as $item) {
                $quotation->items()->create([
                    'product_id' => $item->product_id,
                    'item_name' => $item->item_name,
                    'qty' => $item->qty,
                    'selling_price' => $item->unit_price,
                    'discount_percent' => 0,
                    'net_price' => $item->unit_price,
                    'total' => $item->line_total,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                ]);
            }

            $quotationWorkflowService = app(QuotationWorkflowService::class);
            $quotationWorkflowService->send($quotation);
            $invoice = $quotationWorkflowService->confirm($quotation->refresh());

            $invoice->update([
                'order_id' => $order->id,
                'organisation_name' => $vendor->organization?->name,
                'contact_name' => $order->consumer?->name,
                'billing_address' => $order->billing_address,
                'shipping_address' => $order->shipping_address,
                'payment_method' => $order->payment_method,
                'status' => 'paid',
                'received_amount' => $subtotal,
                'balance' => 0,
                'description' => trim((string) ($invoice->description.'\nVendor: '.$vendor->name)),
            ]);

            $this->deductInventoryForInvoice($invoice->refresh());

            $order->update([
                'invoice_id' => $invoice->id,
                'paid_at' => now(),
                'total_amount' => $subtotal,
                'status' => 'confirmed',
            ]);

            app(CommissionService::class)->generateForInvoice($invoice->refresh());
            app(OrganizationMailService::class)->sendOrderStatusUpdate($order->refresh(), 'confirmed');

            return $invoice->refresh();
        });
    }

    public function confirm(Order $order): Order
    {
        if ($order->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending orders can be confirmed.',
            ]);
        }

        if (! $order->invoice_id) {
            throw ValidationException::withMessages([
                'invoice_id' => 'Payment must be completed to generate invoice before confirmation.',
            ]);
        }

        $order->update(['status' => 'confirmed']);
        app(OrganizationMailService::class)->sendOrderStatusUpdate($order->refresh(), 'confirmed');

        return $order->refresh();
    }

    public function process(Order $order): Order
    {
        if ($order->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'status' => 'Only confirmed orders can move to processing.',
            ]);
        }

        $order->update(['status' => 'processing']);
        app(OrganizationMailService::class)->sendOrderStatusUpdate($order->refresh(), 'processing');
        app(OrganizationMailService::class)->sendInvoiceAndRevenueUpdate($order->refresh());

        return $order->refresh();
    }

    public function ship(Order $order): Order
    {
        if ($order->status !== 'processing') {
            throw ValidationException::withMessages([
                'status' => 'Only processing orders can be shipped.',
            ]);
        }

        $order->update(['status' => 'shipped']);
        app(OrganizationMailService::class)->sendOrderStatusUpdate($order->refresh(), 'shipped');

        return $order->refresh();
    }

    public function deliver(Order $order): Order
    {
        if ($order->status !== 'shipped') {
            throw ValidationException::withMessages([
                'status' => 'Only shipped orders can be delivered.',
            ]);
        }

        $order->update(['status' => 'delivered']);
        app(OrganizationMailService::class)->sendOrderStatusUpdate($order->refresh(), 'delivered');

        return $order->refresh();
    }

    private function nextQuotationNumber(): string
    {
        $lastId = (int) Quotation::query()->max('id') + 1;

        return sprintf('QUO-%s-%04d', now()->format('Y'), $lastId);
    }

    private function deductInventoryForInvoice(Invoice $invoice): void
    {
        $invoice->loadMissing('items', 'quotation.vendor');

        $vendorId = (int) ($invoice->quotation?->vendor_id ?? 0);
        $organizationId = (int) ($invoice->quotation?->vendor?->organization_id ?? 0);
        $ownerTypes = [User::class, 'App\\Models\\User', 'aApp\\Models\\User'];

        foreach ($invoice->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $requiredQty = (float) $item->qty;
            if ($requiredQty <= 0) {
                continue;
            }

            $inventoryRows = Inventory::query()
                ->where('product_id', $item->product_id)
                ->whereIn('owner_type', $ownerTypes)
                ->whereHasMorph('owner', [User::class], function ($q) use ($organizationId): void {
                    if ($organizationId > 0) {
                        $q->where('organization_id', $organizationId);
                    }
                })
                ->orderByRaw('CASE WHEN owner_id = ? THEN 0 ELSE 1 END', [$vendorId])
                ->orderByDesc('quantity_available')
                ->lockForUpdate()
                ->get();

            if ($inventoryRows->isEmpty()) {
                throw ValidationException::withMessages([
                    'inventory' => "Inventory record not found for product [{$item->item_name}] in this organization.",
                ]);
            }

            $totalAvailable = (float) $inventoryRows->sum(fn (Inventory $row): float => (float) $row->quantity_available);
            if ($totalAvailable < $requiredQty) {
                throw ValidationException::withMessages([
                    'inventory' => sprintf(
                        'Insufficient stock for [%s]. Required: %s, Available across organization: %s.',
                        $item->item_name,
                        number_format($requiredQty, 3),
                        number_format($totalAvailable, 3)
                    ),
                ]);
            }

            $remaining = $requiredQty;
            foreach ($inventoryRows as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $available = (float) $inventory->quantity_available;
                if ($available <= 0) {
                    continue;
                }

                $consume = min($available, $remaining);
                $inventory->update([
                    'quantity_available' => $available - $consume,
                    'updated_at' => now(),
                ]);

                $remaining -= $consume;
            }
        }
    }
}
