<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\CustomRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Organization;
use App\Models\PlatformSetting;
use App\Observers\OrganizationObserver;
use App\Models\Product;
use App\Observers\InvoiceItemObserver;
use App\Observers\InvoiceObserver;
use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Throwable;

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

        $this->app->bind(
            \Filament\Auth\Http\Responses\Contracts\LogoutResponse::class,
            \App\Support\FilamentLogoutResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $defaultTimezone = config('app.timezone', 'UTC');
        $platformTimezone = $defaultTimezone;

        try {
            $platformTimezone = Cache::remember('platform.timezone', 300, function () use ($defaultTimezone) {
                $record = PlatformSetting::query()->first();
                $settings = (array) ($record?->settings ?? []);

                return $settings['timezone'] ?? $defaultTimezone;
            });
        } catch (Throwable $e) {
            $platformTimezone = $defaultTimezone;
        }

        if (is_string($platformTimezone) && $platformTimezone !== '') {
            config(['app.timezone' => $platformTimezone]);
            date_default_timezone_set($platformTimezone);
        }

        Route::pattern(
            'tenant',
            '^(?!super-admin$|platform$|login$|forgot-password$|reset-password$|up$|filament$|livewire.*$)[A-Za-z0-9][A-Za-z0-9\-]*$'
        );

        Invoice::observe(InvoiceObserver::class);
        InvoiceItem::observe(InvoiceItemObserver::class);
        Order::observe(OrderObserver::class);
        Organization::observe(OrganizationObserver::class);

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
