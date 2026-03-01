<?php

namespace App\Providers;

use App\Events\InvoiceApprovedEvent;
use App\Events\InvoiceCreatedEvent;
use App\Listeners\GenerateInvoiceCommissions;
use App\Listeners\SendInvoiceCreatedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceCreatedEvent::class => [
            SendInvoiceCreatedNotification::class,
        ],
        InvoiceApprovedEvent::class => [
            GenerateInvoiceCommissions::class,
        ],
    ];
}
