<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Observers\InvoiceItemObserver;
use App\Observers\InvoiceObserver;
use App\Observers\OrderObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Invoice::observe(InvoiceObserver::class);
        InvoiceItem::observe(InvoiceItemObserver::class);
        Order::observe(OrderObserver::class);
    }
}
