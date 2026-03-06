<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use App\Services\CommissionService;

class InvoiceItemObserver
{
    public function saved(InvoiceItem $invoiceItem): void
    {
        $invoice = $invoiceItem->invoice()->first();
        if (! $invoice) {
            return;
        }

        app(CommissionService::class)->syncForInvoice($invoice->fresh());
    }

    public function deleted(InvoiceItem $invoiceItem): void
    {
        $invoice = $invoiceItem->invoice()->first();
        if (! $invoice) {
            return;
        }

        app(CommissionService::class)->syncForInvoice($invoice->fresh());
    }
}
