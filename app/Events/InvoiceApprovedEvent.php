<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceApprovedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }
}
