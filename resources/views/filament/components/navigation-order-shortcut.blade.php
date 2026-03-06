<div class="fi-sidebar-nav-groups fi-sidebar-nav-groups-navigation-order">
    <livewire:navigation-tabs-customizer
        :key="'navigation-tabs-customizer-'.(filament()->getCurrentPanel()?->getId() ?? 'panel').'-'.(auth()->id() ?? 'guest')"
    />
</div>

<style>
    .fi-sidebar-nav-groups-navigation-order {
        /* width: 100%; */
        max-width: 100%;
        margin: 0 auto 0.65rem;
        padding: 0.35rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.25rem;
        border-radius: 0.75rem;
        /* border: 1px solid color-mix(in oklab, var(--gray-300) 70%, transparent); */
        /* background: color-mix(in oklab, var(--gray-50) 85%, transparent); */
    }

    .fi-sidebar-customize-tabs-trigger {
        border-radius: 0.625rem;
        display: inline-flex;
        align-items: center;
        min-height: 2.2rem;
        padding-inline: 0.45rem;
    }

    .fi-sidebar-customize-tabs-trigger-label {
        white-space: nowrap;
    }

    @media (min-width: 1024px) {
        .fi-main-sidebar:not(.fi-sidebar-open) .fi-sidebar-customize-tabs-trigger {
            min-width: 2.2rem;
            padding-inline: 0.45rem;
            justify-content: center;
            gap: 0;
        }

        .fi-main-sidebar:not(.fi-sidebar-open) .fi-sidebar-customize-tabs-trigger-label {
            display: none;
        }

        .fi-main-sidebar.fi-sidebar-open .fi-sidebar-customize-tabs-trigger-label {
            display: inline;
        }
    }

    .dark .fi-sidebar-nav-groups-navigation-order {
        /* border-color: color-mix(in oklab, var(--gray-700) 68%, transparent); */
        background: color-mix(in oklab, var(--gray-900) 55%, transparent);
    }
</style>
