<?php

namespace App\Listeners;

use App\Events\InvoiceApprovedEvent;
use App\Services\CommissionService;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateInvoiceCommissions implements ShouldQueue
{
    public function __construct(private readonly CommissionService $commissionService)
    {
    }

    public function handle(InvoiceApprovedEvent $event): void
    {
        $this->commissionService->generateForInvoice($event->invoice);
    }
}
