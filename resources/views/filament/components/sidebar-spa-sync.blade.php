<script>
    (() => {
        const SCROLL_KEY = 'fi-sidebar-scroll-top';

        const getAllGroupLabels = () =>
            Array.from(
                document.querySelectorAll('.fi-main-sidebar .fi-sidebar-group[data-group-label]'),
            )
                .map((group) => group.dataset.groupLabel)
                .filter((label) => typeof label === 'string' && label.length > 0);

        const getActiveGroupLabel = () => {
            const activeGroup = document.querySelector(
                '.fi-main-sidebar .fi-sidebar-group.fi-active[data-group-label]',
            );

            return activeGroup?.dataset?.groupLabel ?? null;
        };

        const persistCollapsedGroups = (collapsedGroups) => {
            localStorage.setItem('collapsedGroups', JSON.stringify(collapsedGroups));
        };

        const setCollapsedGroups = (collapsedGroups) => {
            if (!window.Alpine?.store) {
                persistCollapsedGroups(collapsedGroups);
                return;
            }

            const sidebar = window.Alpine.store('sidebar');
            if (!sidebar) {
                persistCollapsedGroups(collapsedGroups);
                return;
            }

            sidebar.collapsedGroups = collapsedGroups;
            persistCollapsedGroups(collapsedGroups);
        };

        const collapseAllExcept = (groupLabel = null) => {
            const labels = getAllGroupLabels();
            if (labels.length === 0) {
                return;
            }

            const collapsedGroups = groupLabel
                ? labels.filter((label) => label !== groupLabel)
                : labels;

            setCollapsedGroups(collapsedGroups);
        };

        const syncGroupsForActivePage = () => {
            const activeGroupLabel = getActiveGroupLabel();
            collapseAllExcept(activeGroupLabel);
        };

        const setUpGroupAccordionBehavior = () => {
            document
                .querySelectorAll('.fi-main-sidebar .fi-sidebar-group .fi-sidebar-group-btn')
                .forEach((button) => {
                    if (button.dataset.crmAccordionBound === '1') {
                        return;
                    }

                    button.dataset.crmAccordionBound = '1';

                    button.addEventListener('click', () => {
                        const parentGroup = button.closest('.fi-sidebar-group');
                        const groupLabel = parentGroup?.dataset?.groupLabel;
                        if (!groupLabel) {
                            return;
                        }

                        requestAnimationFrame(() => {
                            const sidebar = window.Alpine?.store?.('sidebar');
                            const isCollapsed = Array.isArray(sidebar?.collapsedGroups)
                                ? sidebar.collapsedGroups.includes(groupLabel)
                                : false;

                            collapseAllExcept(isCollapsed ? null : groupLabel);
                        });
                    });
                });
        };

        const normalizeSidebarStore = () => {
            if (!window.Alpine?.store) {
                return;
            }

            const sidebar = window.Alpine.store('sidebar');
            if (!sidebar) {
                return;
            }

            // Guard persisted null values so includes/concat are always safe.
            if (!Array.isArray(sidebar.collapsedGroups)) {
                sidebar.collapsedGroups = [];
            }
        };

        const getSidebarNav = () => document.querySelector('.fi-main-sidebar .fi-sidebar-nav');
        const getSidebarRoot = () => document.querySelector('.fi-main-sidebar');

        const setSidebarDesktopCollapsedByDefault = () => {
            if (window.innerWidth < 1024 || !window.Alpine?.store) {
                return;
            }

            const sidebar = window.Alpine.store('sidebar');
            if (!sidebar) {
                return;
            }

            sidebar.isOpenDesktop = false;
            sidebar.isOpen = false;
        };

        const updateSidebarToggleState = () => {
            const sidebarRoot = getSidebarRoot();
            if (!sidebarRoot) {
                return;
            }

            const isOpen = sidebarRoot.classList.contains('fi-sidebar-open');

            document.querySelectorAll('[data-crm-sidebar-toggle]').forEach((button) => {
                button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            document.querySelectorAll('[data-crm-sidebar-toggle-arrow]').forEach((arrow) => {
                arrow.textContent = isOpen ? '<' : '>';
            });
        };

        const setUpSidebarToggleButton = () => {
            document.querySelectorAll('[data-crm-sidebar-toggle]').forEach((button) => {
                if (button.dataset.crmSidebarToggleBound === '1') {
                    return;
                }

                button.dataset.crmSidebarToggleBound = '1';

                button.addEventListener('click', (event) => {
                    event.preventDefault();

                    if (window.innerWidth < 1024) {
                        return;
                    }

                    const sidebar = window.Alpine?.store?.('sidebar');
                    if (!sidebar) {
                        return;
                    }

                    const sidebarRoot = getSidebarRoot();
                    const isOpen = sidebarRoot?.classList.contains('fi-sidebar-open');

                    if (isOpen) {
                        sidebar.close?.();
                    } else {
                        sidebar.open?.();
                    }

                    requestAnimationFrame(updateSidebarToggleState);
                });
            });
        };

        const saveSidebarScroll = () => {
            const sidebarNav = getSidebarNav();
            if (!sidebarNav) {
                return;
            }

            sessionStorage.setItem(SCROLL_KEY, String(sidebarNav.scrollTop || 0));
        };

        const restoreSidebarScroll = () => {
            const sidebarNav = getSidebarNav();
            if (!sidebarNav) {
                return;
            }

            const raw = sessionStorage.getItem(SCROLL_KEY);
            if (raw === null) {
                return;
            }

            const target = Number(raw);
            if (!Number.isFinite(target)) {
                return;
            }

            // Restore immediately without animation to avoid top-jump flicker.
            sidebarNav.scrollTop = Math.max(0, target);
        };

        const boot = () => {
            normalizeSidebarStore();
            setSidebarDesktopCollapsedByDefault();
            setUpGroupAccordionBehavior();
            setUpSidebarToggleButton();
            syncGroupsForActivePage();
            updateSidebarToggleState();

            requestAnimationFrame(() => {
                normalizeSidebarStore();
                setSidebarDesktopCollapsedByDefault();
                restoreSidebarScroll();
                setUpGroupAccordionBehavior();
                setUpSidebarToggleButton();
                syncGroupsForActivePage();
                updateSidebarToggleState();
            });
        };

        document.addEventListener('scroll', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            if (event.target.matches('.fi-main-sidebar .fi-sidebar-nav')) {
                saveSidebarScroll();
            }
        }, true);

        document.addEventListener('livewire:navigate', saveSidebarScroll);
        document.addEventListener('alpine:init', normalizeSidebarStore);
        document.addEventListener('DOMContentLoaded', boot);
        document.addEventListener('livewire:navigated', boot);
    })();
</script>

<style>
    html {
        scroll-behavior: smooth;
    }

    @media (min-width: 1024px) {
        .fi-main-ctn {
            transition: margin-inline-start 180ms ease;
            min-height: calc(100vh - 4.25rem);
            min-height: calc(100dvh - 4.25rem);
            height: auto;
            overflow: visible;
        }

        .fi-main-sidebar.fi-sidebar-open {
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.16);
        }

        .fi-main {
            border: 2px solid color-mix(in oklab, var(--primary-500) 25%, #cbd5e1);
            border-radius: 0.9rem;
            box-sizing: border-box;
            /* width: 100%; */
            width: calc(100% - 1rem);
            max-width: 100%;
            padding: 1rem 1.05rem;
            background: linear-gradient(
                180deg,
                color-mix(in oklab, #ffffff 94%, var(--primary-50)) 0%,
                #ffffff 100%
            );
            min-height: calc(100vh - 5.25rem);
            min-height: calc(100dvh - 5.25rem);
            height: auto;
            overflow: visible;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
            display: flex;
            flex-direction: column;
            margin-left: 0rem;
            margin-right: 1rem;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .fi-main-ctn.fi-main-ctn-sidebar-open .fi-main {
            border-color: color-mix(in oklab, var(--primary-500) 42%, #9ca3af);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.09);
        }

        .fi-main > .fi-page {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding-inline-end: 0.25rem;
            max-width: 100%;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: color-mix(in oklab, var(--primary-500) 42%, #94a3b8) transparent;
        }

        .fi-main > .fi-page::-webkit-scrollbar {
            width: 6px;
        }

        .fi-main > .fi-page::-webkit-scrollbar-track {
            background: transparent;
        }

        .fi-main > .fi-page::-webkit-scrollbar-thumb {
            background: color-mix(in oklab, var(--primary-500) 45%, #94a3b8);
            border-radius: 9999px;
            border: 1px solid transparent;
            background-clip: padding-box;
        }

        .fi-main > .fi-page::-webkit-scrollbar-thumb:hover {
            background: color-mix(in oklab, var(--primary-600) 55%, #64748b);
        }
    }

    @media (min-width: 1280px) {
        .fi-main {
            padding: 1.3rem 1.4rem;
        }
    }

    .dark .fi-main {
        background: color-mix(in oklab, #000000 72%, transparent);
        border-color: color-mix(in oklab, var(--primary-400) 38%, #475569);
        box-shadow: 0 10px 26px rgba(2, 6, 23, 0.35);
    }

    .fi-main-sidebar .fi-sidebar-nav {
        scrollbar-width: none;
        scrollbar-color: transparent transparent;
        /* padding-inline-end: 0.35rem; */
        scroll-behavior: smooth;
    }

    .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar {
        width: 0;
    }

    .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar-track {
        background: transparent;
    }

    .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar-thumb {
        background: transparent;
        border-radius: 9999px;
        border: 1px solid transparent;
        background-clip: padding-box;
    }

    .fi-main-sidebar .fi-sidebar-nav:hover::-webkit-scrollbar-thumb {
        background: color-mix(in oklab, var(--primary-600) 55%, #64748b);
    }

    .fi-main-sidebar:hover .fi-sidebar-nav,
    .fi-main-sidebar .fi-sidebar-nav:hover,
    .fi-main-sidebar .fi-sidebar-nav:focus-within {
        scrollbar-width: thin;
        scrollbar-color: color-mix(in oklab, var(--primary-500) 42%, #94a3b8) transparent;
    }

    .fi-main-sidebar:hover .fi-sidebar-nav::-webkit-scrollbar,
    .fi-main-sidebar .fi-sidebar-nav:hover::-webkit-scrollbar,
    .fi-main-sidebar .fi-sidebar-nav:focus-within::-webkit-scrollbar {
        width: 6px;
    }

    .fi-main-sidebar:hover .fi-sidebar-nav::-webkit-scrollbar-thumb,
    .fi-main-sidebar .fi-sidebar-nav:hover::-webkit-scrollbar-thumb,
    .fi-main-sidebar .fi-sidebar-nav:focus-within::-webkit-scrollbar-thumb {
        background: color-mix(in oklab, var(--primary-500) 45%, #94a3b8);
    }

    .fi-main-sidebar .fi-sidebar-group > .fi-sidebar-group-btn {
        border-radius: 0.525rem;
        border: 1px solid color-mix(in oklab, var(--gray-300) 70%, transparent);
        background: color-mix(in oklab, #ffffff 90%, var(--gray-100));
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.05),
            0 6px 14px rgba(15, 23, 42, 0.06);
        transition: box-shadow 180ms ease, background-color 180ms ease, border-color 180ms ease;
    }

    .fi-main-sidebar .fi-sidebar-group > .fi-sidebar-group-btn:hover {
        border-color: color-mix(in oklab, var(--primary-500) 28%, #cbd5e1);
        background: color-mix(in oklab, #ffffff 82%, var(--primary-50));
    }

    .fi-main-sidebar .fi-sidebar-group.fi-active > .fi-sidebar-group-btn {
        border-color: color-mix(in oklab, var(--primary-500) 56%, #93c5fd);
        background: linear-gradient(
            135deg,
            color-mix(in oklab, var(--primary-100) 76%, #ffffff) 0%,
            color-mix(in oklab, var(--primary-50) 78%, #ffffff) 100%
        );
        color: color-mix(in oklab, var(--primary-700) 82%, #0f172a);
        box-shadow:
            0 0 0 1px color-mix(in oklab, var(--primary-500) 34%, transparent),
            0 10px 24px rgba(14, 116, 144, 0.18);
    }

    .fi-main-sidebar .fi-sidebar-group.fi-active > .fi-sidebar-group-btn svg {
        color: color-mix(in oklab, var(--primary-600) 88%, #0f172a);
    }

    .dark .fi-main-sidebar .fi-sidebar-nav {
        scrollbar-color: transparent transparent;
    }

    .dark .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar-thumb {
        background: transparent;
    }

    .dark .fi-main-sidebar:hover .fi-sidebar-nav,
    .dark .fi-main-sidebar .fi-sidebar-nav:hover,
    .dark .fi-main-sidebar .fi-sidebar-nav:focus-within {
        scrollbar-color: color-mix(in oklab, var(--primary-400) 48%, #475569) transparent;
    }

    .dark .fi-main-sidebar:hover .fi-sidebar-nav::-webkit-scrollbar-thumb,
    .dark .fi-main-sidebar .fi-sidebar-nav:hover::-webkit-scrollbar-thumb,
    .dark .fi-main-sidebar .fi-sidebar-nav:focus-within::-webkit-scrollbar-thumb {
        background: color-mix(in oklab, var(--primary-400) 52%, #334155);
    }

    .dark .fi-main-sidebar .fi-sidebar-group.fi-active > .fi-sidebar-group-btn {
        border-color: color-mix(in oklab, var(--primary-400) 62%, #475569);
        background: linear-gradient(
            135deg,
            color-mix(in oklab, var(--primary-900) 55%, #020617) 0%,
            color-mix(in oklab, var(--primary-800) 45%, #0f172a) 100%
        );
        color: color-mix(in oklab, var(--primary-200) 80%, #e2e8f0);
        box-shadow:
            0 0 0 1px color-mix(in oklab, var(--primary-400) 34%, transparent),
            0 12px 28px rgba(2, 6, 23, 0.5);
    }

    .dark .fi-main-sidebar .fi-sidebar-group > .fi-sidebar-group-btn {
        border-color: color-mix(in oklab, var(--gray-700) 70%, transparent);
        background: color-mix(in oklab, #000000 90%, var(--gray-900));
        box-shadow:
            0 1px 2px rgba(2, 6, 23, 0.35),
            0 8px 16px rgba(2, 6, 23, 0.32);
    }

    .dark .fi-main-sidebar .fi-sidebar-group > .fi-sidebar-group-btn:hover {
        border-color: color-mix(in oklab, var(--primary-400) 45%, #334155);
        background: color-mix(in oklab, var(--primary-900) 30%, #0f172a);
    }

    .dark .fi-main-sidebar .fi-sidebar-group.fi-active > .fi-sidebar-group-btn svg {
        color: color-mix(in oklab, var(--primary-300) 88%, #f8fafc);
    }

    .dark .fi-main > .fi-page {
        scrollbar-color: color-mix(in oklab, var(--primary-400) 52%, #475569) transparent;
    }

    .dark .fi-main > .fi-page::-webkit-scrollbar-thumb {
        background: color-mix(in oklab, var(--primary-400) 52%, #334155);
    }
</style>
