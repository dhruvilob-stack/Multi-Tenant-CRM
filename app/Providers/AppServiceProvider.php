<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\CustomRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Observers\InvoiceItemObserver;
use App\Observers\InvoiceObserver;
use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Filament\Auth\Http\Responses\Contracts\LoginResponse::class,
            \App\Support\FilamentLoginResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::pattern(
            'tenant',
            '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|up$|filament$|livewire.*$)[A-Za-z0-9][A-Za-z0-9\-]*$'
        );

        Invoice::observe(InvoiceObserver::class);
        InvoiceItem::observe(InvoiceItemObserver::class);
        Order::observe(OrderObserver::class);

        $autoSlug = function (Model $record, string $sourceField = 'name'): void {
            $currentSlug = trim((string) ($record->getAttribute('slug') ?? ''));
            if ($currentSlug !== '') {
                return;
            }

            $source = trim((string) ($record->getAttribute($sourceField) ?? ''));
            if ($source === '') {
                return;
            }

            $base = Str::slug($source);
            if ($base === '') {
                return;
            }

            $candidate = $base;
            $suffix = 1;

            while (
                $record::query()
                    ->where('slug', $candidate)
                    ->when($record->exists, fn ($q) => $q->whereKeyNot($record->getKey()))
                    ->exists()
            ) {
                $candidate = "{$base}-{$suffix}";
                $suffix++;
            }

            $record->setAttribute('slug', $candidate);
        };

        Organization::saving(fn (Organization $record) => $autoSlug($record));
        Category::saving(fn (Category $record) => $autoSlug($record));
        Product::saving(fn (Product $record) => $autoSlug($record));
        CustomRole::saving(fn (CustomRole $record) => $autoSlug($record));
    }
}
