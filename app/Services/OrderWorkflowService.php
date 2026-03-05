<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quotation;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductCustomerPurchase;
use App\Models\User;
use App\Support\QuotationStatus;
use App\Support\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function markPaidAndGenerateInvoice(Order $order, bool $sendStatusMail = true): Invoice
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

        return DB::transaction(function () use ($order, $vendor, $distributor, $subtotal, $sendStatusMail): Invoice {
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
                $discountPercent = max(0, min(100, (float) ($item->discount_percent ?? 0)));
                $netPrice = round((float) $item->unit_price * (1 - ($discountPercent / 100)), 2);

                $quotation->items()->create([
                    'product_id' => $item->product_id,
                    'item_name' => $item->item_name,
                    'qty' => $item->qty,
                    'selling_price' => $item->unit_price,
                    'discount_percent' => $discountPercent,
                    'net_price' => $netPrice,
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

            $order->update([
                'invoice_id' => $invoice->id,
                'paid_at' => now(),
                'total_amount' => $subtotal,
                'total_amount_billed' => $subtotal,
            ]);

            app(CommissionService::class)->generateForInvoice($invoice->refresh());

            if ($sendStatusMail) {
                app(OrganizationMailService::class)->sendOrderStatusUpdate($order->refresh(), (string) $order->status);
            }

            return $invoice->refresh();
        });
    }

    public function sendDeliveryMailAndEnsureInvoice(Order $order): Invoice
    {
        $order->loadMissing('invoice', 'items', 'consumer', 'vendor.organization');

        if ((string) $order->status !== 'delivered') {
            throw ValidationException::withMessages([
                'status' => 'This action is allowed only for delivered orders.',
            ]);
        }

        if ((string) $order->payment_status !== 'confirmed') {
            throw ValidationException::withMessages([
                'payment_status' => 'Payment must be confirmed before sending delivery invoice mail.',
            ]);
        }

        $invoice = $order->invoice_id
            ? Invoice::query()->findOrFail($order->invoice_id)
            : $this->markPaidAndGenerateInvoice($order, sendStatusMail: false);

        // If an existing invoice was reused, make sure it is linked and paid before generating commissions.
        $invoiceUpdates = [];
        if (! $invoice->order_id) {
            $invoiceUpdates['order_id'] = $order->id;
        }

        if (! in_array((string) $invoice->status, ['approved', 'paid'], true)) {
            $invoiceUpdates['status'] = 'paid';
            $invoiceUpdates['received_amount'] = (float) $invoice->grand_total;
            $invoiceUpdates['balance'] = 0;
        }

        if ($invoiceUpdates !== []) {
            $invoice->update($invoiceUpdates);
            $invoice = $invoice->refresh();
        }

        app(CommissionService::class)->generateForInvoice($invoice->refresh());

        app(OrganizationMailService::class)->sendDeliveredInvoiceToCustomer($order->fresh(['consumer', 'vendor.organization']), $invoice->fresh());

        return $invoice->fresh();
    }

    public function confirm(Order $order): Order
    {
        if ($order->status !== 'new') {
            throw ValidationException::withMessages([
                'status' => 'Only new orders can be confirmed.',
            ]);
        }

        if (! $order->invoice_id) {
            throw ValidationException::withMessages([
                'invoice_id' => 'Payment must be completed to generate invoice before confirmation.',
            ]);
        }

        $order->update(['status' => 'processing']);
        app(OrganizationMailService::class)->sendOrderStatusUpdate($order->refresh(), 'processing');

        return $order->refresh();
    }

    public function process(Order $order): Order
    {
        if ($order->status !== 'new') {
            throw ValidationException::withMessages([
                'status' => 'Only new orders can move to processing.',
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

        if ((string) $order->payment_status !== 'confirmed') {
            throw ValidationException::withMessages([
                'payment_status' => 'Only confirmed-payment orders can be delivered.',
            ]);
        }

        DB::transaction(function () use ($order): void {
            $this->deductInventoryForOrder($order->fresh(['items', 'vendor']));
        });

        $order->update(['status' => 'delivered']);
        app(OrganizationMailService::class)->sendOrderStatusUpdate($order->refresh(), 'delivered');

        return $order->refresh();
    }

    public function syncDeliveredPaidOrderEffects(Order $order): void
    {
        if ((string) $order->status !== 'delivered') {
            throw ValidationException::withMessages([
                'status' => 'Order must be delivered to sync inventory and purchases.',
            ]);
        }

        if ((string) $order->payment_status !== 'confirmed') {
            throw ValidationException::withMessages([
                'payment_status' => 'Payment must be confirmed to sync inventory and purchases.',
            ]);
        }

        DB::transaction(function () use ($order): void {
            $this->deductInventoryForOrder($order->fresh(['items', 'vendor']));
        });
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

    private function deductInventoryForOrder(Order $order): void
    {
        $order->loadMissing('items', 'vendor');

        $vendorId = (int) ($order->vendor_id ?? 0);
        $organizationId = (int) ($order->vendor?->organization_id ?? 0);
        $ownerTypes = [User::class, 'App\\Models\\User', 'aApp\\Models\\User'];

        foreach ($order->items as $item) {
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

            $product = Product::query()
                ->lockForUpdate()
                ->find($item->product_id);

            if ($product) {
                $currentQty = (float) ($product->qty ?? 0);
                $currentPurchased = (float) ($product->purchased_qty ?? 0);

                $product->update([
                    'qty' => max((int) floor($currentQty - $requiredQty), 0),
                    'purchased_qty' => round($currentPurchased + $requiredQty, 3),
                ]);
            }

            $consumerId = (int) ($order->consumer_id ?? 0);
            if ($consumerId > 0) {
                $purchase = ProductCustomerPurchase::query()
                    ->lockForUpdate()
                    ->firstOrNew([
                        'product_id' => (int) $item->product_id,
                        'consumer_id' => $consumerId,
                    ]);

                $purchase->purchased_qty = round((float) ($purchase->purchased_qty ?? 0) + $requiredQty, 3);
                $purchase->save();
            }
        }
    }
}
