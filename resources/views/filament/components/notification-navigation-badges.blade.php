<style>
    .fi-sidebar-item-label .fi-nav-notification-badge {
        margin-inline-start: 0.45rem;
        display: inline-flex;
        min-width: 1.3rem;
        height: 1.3rem;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding-inline: 0.35rem;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 1;
        color: #fff;
        background: linear-gradient(135deg, #f97316, #ef4444);
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.8);
    }

    .dark .fi-sidebar-item-label .fi-nav-notification-badge {
        color: #111827;
        background: linear-gradient(135deg, #fde68a, #facc15);
        box-shadow: 0 0 0 1px rgba(17, 24, 39, 0.5);
    }
</style>

<script>
    (function () {
        const countsUrl = @js(route('filament.notifications.sections.counts'));
        const markReadUrl = @js(route('filament.notifications.sections.read'));
        const csrfToken = @js(csrf_token());
        const userId = @js(auth()->id());
        let currentSectionPath = null;
        let inFlightCounts = false;

        const singularize = (value) => {
            if (! value) return value;
            if (value.endsWith('ies')) return value.slice(0, -3) + 'y';
            if (value.endsWith('sses')) return value.slice(0, -2);
            if (value.endsWith('s')) return value.slice(0, -1);
            return value;
        };

        const panelAndSectionFromUrl = (href) => {
            try {
                const url = new URL(href, window.location.origin);
                const parts = url.pathname.split('/').filter(Boolean);
                if (parts.length < 2) return { panel: null, section: null };
                return {
                    panel: parts[0],
                    section: parts.slice(1).join('/'),
                    parentSection: parts[1] || null,
                };
            } catch (e) {
                return { panel: null, section: null, parentSection: null };
            }
        };

        const setBadge = (link, count) => {
            const label = link.querySelector('.fi-sidebar-item-label');
            if (! label) return;

            let badge = label.querySelector('.fi-nav-notification-badge');

            if (! count || count < 1) {
                badge?.remove();
                return;
            }

            if (! badge) {
                badge = document.createElement('span');
                badge.className = 'fi-nav-notification-badge';
                label.appendChild(badge);
            }

            badge.textContent = String(count);
        };

        const applyCounts = (counts) => {
            const links = document.querySelectorAll('.fi-sidebar-item.fi-sidebar-item-has-url > a.fi-sidebar-item-btn[href]');

            links.forEach((link) => {
                const href = link.getAttribute('href') || '';
                const { section, parentSection } = panelAndSectionFromUrl(href);

                if (! section) {
                    setBadge(link, 0);
                    return;
                }

                const normalized = section.toLowerCase();
                const parent = (parentSection || '').toLowerCase();
                const isMailSection = normalized.startsWith('mail/');
                const isInboxSection = normalized === 'mail/inbox-mail';
                const count = Number(
                    counts[normalized] ??
                    ((isMailSection && !isInboxSection) ? 0 : (counts[parent] ?? null)) ??
                    counts[singularize(normalized)] ??
                    ((isMailSection && !isInboxSection) ? 0 : (counts[singularize(parent)] ?? null)) ??
                    0
                );

                setBadge(link, count);
            });
        };

        const fetchCounts = async () => {
            if (inFlightCounts) return;
            inFlightCounts = true;

            try {
                const response = await fetch(countsUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (! response.ok) return;
                const payload = await response.json();
                applyCounts(payload.counts || {});
            } catch (e) {
                // ignore
            } finally {
                inFlightCounts = false;
            }
        };

        const markCurrentSectionAsRead = async () => {
            const parts = window.location.pathname.split('/').filter(Boolean);
            if (parts.length < 2) return;

            const section = singularize((parts[1] || '').toLowerCase());
            if (! section) return;
            const sectionPath = `${parts[0]}/${section}`;
            if (currentSectionPath === sectionPath) return;
            currentSectionPath = sectionPath;

            try {
                const response = await fetch(markReadUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ section }),
                });

                if (! response.ok) return;
                const payload = await response.json();
                applyCounts(payload.counts || {});
            } catch (e) {
                // ignore
            }
        };

        const boot = () => {
            markCurrentSectionAsRead();
        };

        document.addEventListener('DOMContentLoaded', () => {
            fetchCounts();
            boot();
            setInterval(fetchCounts, 15000);
        });
        document.addEventListener('livewire:navigated', boot);
        window.addEventListener('mail-counts-updated', fetchCounts);

        if (window.Echo && userId) {
            window.Echo.private(`notifications.${userId}`)
                .listen('.ResourceNotificationCreated', () => {
                    fetchCounts();
                });
        }
    })();
</script>
