<?php

namespace App\Services;

use App\Events\InvoiceApprovedEvent;
use App\Models\Invoice;

class InvoiceWorkflowService
{
    public function approve(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => 'approved']);
        InvoiceApprovedEvent::dispatch($invoice);

        return $invoice->refresh();
    }

    public function markPaid(Invoice $invoice, ?float $received = null): Invoice
    {
        $receivedAmount = $received ?? (float) $invoice->grand_total;
        $balance = max((float) $invoice->grand_total - $receivedAmount, 0);

        $invoice->update([
            'status' => 'paid',
            'received_amount' => $receivedAmount,
            'balance' => $balance,
        ]);

        InvoiceApprovedEvent::dispatch($invoice);

        return $invoice->refresh();
    }
}
