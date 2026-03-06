<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Order;
use App\Services\CommissionService;

class OrderObserver
{
    public function saved(Order $order): void
    {
        if (! $order->invoice_id) {
            return;
        }

        $invoice = Invoice::query()->find($order->invoice_id);
        if (! $invoice) {
            return;
        }

        app(CommissionService::class)->syncForInvoice($invoice->fresh());
    }

    public function deleting(Order $order): void
    {
        if ($order->invoice_id) {
            $invoice = Invoice::query()->find($order->invoice_id);
            if ($invoice) {
                app(CommissionService::class)->clearForInvoice($invoice);
            }
        }

        Invoice::query()
            ->where('order_id', $order->id)
            ->get()
            ->each(fn (Invoice $invoice): mixed => app(CommissionService::class)->clearForInvoice($invoice));
    }
}
