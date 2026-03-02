<style>
    .fi-topbar {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .fi-topbar .fi-global-search-ctn {
        order: 50;
        width: 100%;
        flex-basis: 100%;
    }

    .fi-topbar .fi-global-search,
    .fi-topbar .fi-global-search-field,
    .fi-topbar .fi-global-search-field > * {
        width: 100%;
        max-width: 100%;
    }

    .fi-topbar .fi-global-search input[type='search'] {
        width: 100%;
        max-width: 100%;
    }

    .fi-topbar .fi-global-search .fi-input-wrp {
        border-color: rgba(14, 165, 233, 0.3);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(236, 253, 245, 0.92));
    }

    .fi-topbar .fi-global-search-results-ctn {
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 0.9rem;
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.14);
    }

    @media (min-width: 1024px) {
        .fi-topbar {
            position: relative;
            flex-wrap: nowrap;
            gap: 0;
        }

        .fi-topbar .fi-global-search-ctn {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: min(44rem, calc(100vw - 8rem));
            display: flex;
            justify-content: center;
            pointer-events: none;
            order: initial;
            flex-basis: auto;
        }

        .fi-topbar .fi-global-search {
            width: 100%;
            pointer-events: auto;
        }

        .fi-topbar .fi-global-search-results-ctn {
            inset-inline: auto !important;
            left: 50% !important;
            right: auto !important;
            width: min(44rem, calc(100vw - 8rem)) !important;
            max-width: none !important;
            transform: translateX(-50%) translateZ(0) !important;
        }
    }

    .dark .fi-topbar .fi-global-search .fi-input-wrp {
        border-color: rgba(250, 204, 21, 0.4);
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.94), rgba(15, 23, 42, 0.96));
    }
</style>
