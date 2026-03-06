<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\CommissionService;

class InvoiceObserver
{
    public function saved(Invoice $invoice): void
    {
        app(CommissionService::class)->syncForInvoice($invoice->fresh());
    }

    public function deleting(Invoice $invoice): void
    {
        app(CommissionService::class)->clearForInvoice($invoice);
    }
}
