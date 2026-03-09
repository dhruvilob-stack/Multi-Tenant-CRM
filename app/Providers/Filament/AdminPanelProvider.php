<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ApplyUserLocale;
use App\Http\Middleware\InitializeTenancy;
use App\Http\Middleware\SetTenantUrlDefaults;
use App\Http\Middleware\RedirectPanelLoginToUniversalLogin;
use App\Http\Middleware\SetPanelSessionCookie;
use App\Support\CrmGlobalSearchProvider;
use App\Support\NavigationPreferenceManager;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use CharrafiMed\GlobalSearchModal\GlobalSearchResults as ModalGlobalSearchResults;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationManager;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('{tenant}')
            ->login()
            ->spa(true, true)
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('3.25rem')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('DN-CRM-Panel')
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
            ->renderHook(PanelsRenderHook::BODY_END, fn() => view('filament.components.mail-composer-popup'))
            ->renderHook(PanelsRenderHook::BODY_END, fn() => view('filament.components.mail-viewer-popup'))
            ->renderHook(PanelsRenderHook::BODY_END, fn() => view('filament.components.record-highlight'))
            ->renderHook(PanelsRenderHook::TOPBAR_START, fn() => view('filament.components.topbar-role-label'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn() => view('filament.components.notification-topbar-theme'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn() => view('filament.components.global-search-responsive'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn() => view('filament.components.view-readability-theme'))
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn() => view('filament.components.notification-row-highlight'))
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn() => view('filament.components.notification-navigation-badges'))
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn() => view('filament.components.sidebar-spa-sync'))
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, fn() => view('filament.components.navigation-order-shortcut'))
            ->navigation(
                fn(NavigationBuilder $builder, NavigationManager $navigationManager): NavigationBuilder => app(NavigationPreferenceManager::class)
                    ->applyToBuilderForCurrentUser($builder, $panel, $navigationManager)
            )
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                SetPanelSessionCookie::class,
                StartSession::class,
                InitializeTenancy::class,
                SetTenantUrlDefaults::class,
                RedirectPanelLoginToUniversalLogin::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                ApplyUserLocale::class,
            ])
            ->authGuard('tenant');
    }
}