<style>
    .fi-topbar .fi-global-search .fi-input-wrp {
        border-color: rgba(14, 165, 233, 0.3);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(236, 253, 245, 0.92));
    }

    .fi-topbar .fi-global-search-results-ctn {
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 0.9rem;
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.14);
    }

    .dark .fi-topbar .fi-global-search .fi-input-wrp {
        border-color: rgba(250, 204, 21, 0.4);
        background: linear-gradient(135deg, rgba(16, 15, 15, 0.94), rgba(0, 0, 0, 0.96));
    }

    @media (max-width: 1023px) {
        .fi-topbar {
            position: relative;
        }

        .fi-topbar .fi-topbar-end > .fi-global-search-ctn {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: min(14rem, calc(100vw - 9rem));
            z-index: 8;
            pointer-events: none;
        }

        .fi-topbar .fi-topbar-end > .fi-global-search-ctn .fi-global-search {
            pointer-events: auto;
            width: 100%;
        }
    }

    @media (min-width: 1024px) {
        .fi-topbar {
            position: relative;
        }

        .fi-topbar .fi-topbar-start,
        .fi-topbar .fi-topbar-end {
            position: relative;
            z-index: 20;
        }

        .fi-topbar .fi-topbar-end > .fi-global-search-ctn {
            position: absolute;
            top: 50%;
            left: var(--crm-search-center, 50%);
            width: var(--crm-search-width, 28rem);
            max-width: 44rem;
            transform: translate(-50%, -50%);
            z-index: 10;
            pointer-events: none;
        }

        .fi-topbar .fi-topbar-end > .fi-global-search-ctn .fi-global-search {
            pointer-events: auto;
            width: 100%;
        }

        .fi-topbar .fi-topbar-end > .fi-global-search-ctn .fi-global-search-results-ctn {
            inset-inline: auto !important;
            left: 50% !important;
            right: auto !important;
            width: 100% !important;
            max-width: none !important;
            transform: translateX(-50%) translateZ(0) !important;
        }
    }
</style>

<script>
    (() => {
        const MIN_WIDTH = 220;
        const MAX_WIDTH = 704;
        const GAP = 16;

        const getTopbarContext = () => {
            const topbar = document.querySelector('.fi-topbar');
            const topbarStart = topbar?.querySelector('.fi-topbar-start');
            const topbarEnd = topbar?.querySelector('.fi-topbar-end');
            const search = topbarEnd?.querySelector(':scope > .fi-global-search-ctn');

            if (!topbar || !topbarStart || !topbarEnd || !search) {
                return null;
            }

            return { topbar, topbarStart, topbarEnd, search };
        };

        const updateDesktopSearchLayout = () => {
            const context = getTopbarContext();
            if (!context) return;
            const { topbar, topbarStart, topbarEnd } = context;

            if (window.innerWidth < 1024) {
                topbar.style.removeProperty('--crm-search-center');
                topbar.style.removeProperty('--crm-search-width');
                return;
            }

            const topRect = topbar.getBoundingClientRect();
            const startRect = topbarStart.getBoundingClientRect();
            const endRect = topbarEnd.getBoundingClientRect();

            const leftBound = Math.max(GAP, startRect.right - topRect.left + GAP);
            const rightBound = Math.min(topRect.width - GAP, endRect.left - topRect.left - GAP);
            const available = rightBound - leftBound;
            const endOffset = endRect.left - topRect.left;

            if (available <= MIN_WIDTH) {
                // Keep visible in viewport even when space is tight.
                const minCenter = MIN_WIDTH / 2 + GAP;
                const maxCenter = Math.max(minCenter, topRect.width - MIN_WIDTH / 2 - GAP);
                const desiredCenter = topRect.width / 2;
                const fallbackCenter = Math.min(maxCenter, Math.max(minCenter, desiredCenter));
                const fallbackCenterInEnd = fallbackCenter - endOffset;

                topbar.style.setProperty('--crm-search-center', `${fallbackCenterInEnd}px`);
                topbar.style.setProperty('--crm-search-width', `${MIN_WIDTH}px`);
                return;
            }

            const width = Math.min(MAX_WIDTH, available);
            const minCenter = width / 2 + GAP;
            const maxCenter = Math.max(minCenter, topRect.width - width / 2 - GAP);
            const desiredCenter = leftBound + available / 2;
            const center = Math.min(maxCenter, Math.max(minCenter, desiredCenter));
            const centerInEnd = center - endOffset;

            topbar.style.setProperty('--crm-search-center', `${centerInEnd}px`);
            topbar.style.setProperty('--crm-search-width', `${width}px`);
        };

        const boot = () => requestAnimationFrame(updateDesktopSearchLayout);

        window.addEventListener('resize', boot, { passive: true });
        document.addEventListener('DOMContentLoaded', boot);
        document.addEventListener('livewire:navigated', boot);
    })();
</script>
