<?php

namespace App\Listeners;

use App\Events\InvoiceCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendInvoiceCreatedNotification implements ShouldQueue
{
    public function handle(InvoiceCreatedEvent $event): void
    {
        Log::info('Invoice auto-created from quotation.', [
            'invoice_id' => $event->invoice->id,
            'invoice_number' => $event->invoice->invoice_number,
            'quotation_id' => $event->invoice->quotation_id,
        ]);
    }
}
