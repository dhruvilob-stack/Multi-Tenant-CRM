<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ApplyUserLocale;
use App\Http\Middleware\RedirectPanelLoginToUniversalLogin;
use App\Support\CrmGlobalSearchProvider;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use CharrafiMed\GlobalSearchModal\GlobalSearchResults as ModalGlobalSearchResults;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->spa(true, true)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName(fn(): HtmlString => new HtmlString(sprintf(
                'DN-CRM <span class="ml-2 px-3 py-1 text-[11px] uppercase tracking-widest font-semibold rounded-full bg-emerald-500 text-white">%s</span>',
                Str::of(auth()->user()?->role ?? 'guest')->replace('_', ' ')->title(),
            )))
            ->colors([
                'primary' => Color::Teal,
                'info' => Color::Cyan,
                'success' => Color::Emerald,
            ])
            ->globalSearch(\App\Support\CrmGlobalSearchProvider::class)
            ->databaseNotifications()
            ->plugins([
                GlobalSearchModalPlugin::make()
                    ->searchUsing(
                        function (string $query, ModalGlobalSearchResults $builder): ModalGlobalSearchResults {
                            return app(CrmGlobalSearchProvider::class)->getResults($query)
                                ?? $builder;
                        },
                        mergeWithCore: false,
                    ),
            ])
            ->databaseNotificationsPolling('15s')
            ->renderHook(PanelsRenderHook::BODY_START, fn() => view('filament.components.navigation-progress'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn() => view('filament.components.notification-topbar-theme'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn() => view('filament.components.global-search-responsive'))
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn() => view('filament.components.notification-row-highlight'))
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn() => view('filament.components.notification-navigation-badges'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                RedirectPanelLoginToUniversalLogin::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                ApplyUserLocale::class,
            ]);
    }
}
