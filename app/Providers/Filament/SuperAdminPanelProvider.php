<?php

namespace App\Providers\Filament;

use App\Support\CrmGlobalSearchProvider;
use App\Support\NavigationPreferenceManager;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use CharrafiMed\GlobalSearchModal\GlobalSearchResults as ModalGlobalSearchResults;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
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

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('super-admin')
            ->path('super-admin')
            ->login()
            ->spa(true, true)
            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('3.25rem')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('CRM Control Center')
            ->colors([
                'primary' => Color::Blue,
                'warning' => Color::Amber,
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
            ->renderHook(PanelsRenderHook::TOPBAR_START, fn() => view('filament.components.topbar-role-label'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn() => view('filament.components.notification-topbar-theme'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn() => view('filament.components.global-search-responsive'))
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn() => view('filament.components.notification-row-highlight'))
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn() => view('filament.components.notification-navigation-badges'))
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn() => view('filament.components.sidebar-spa-sync'))
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, fn() => view('filament.components.navigation-order-shortcut'))
            ->navigation(
                fn (NavigationBuilder $builder, NavigationManager $navigationManager): NavigationBuilder => app(NavigationPreferenceManager::class)
                    ->applyToBuilderForCurrentUser($builder, $panel, $navigationManager)
            )
            ->discoverResources(in: app_path('Filament/SuperAdmin/Resources'), for: 'App\\Filament\\SuperAdmin\\Resources')
            ->discoverPages(in: app_path('Filament/SuperAdmin/Pages'), for: 'App\\Filament\\SuperAdmin\\Pages')
            ->widgets([])
            ->middleware([
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
            ])
            ->authGuard('web');
    }
}
