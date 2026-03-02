<style>
    @keyframes notificationRowBlink {
        0% { background-color: transparent; }
        50% { background-color: rgba(34, 197, 94, 0.18); }
        100% { background-color: transparent; }
    }

    tr.notification-row-highlight {
        /* class is applied on the <tr>, but we animate the <td> cells for visibility */
    }

    tr.notification-row-highlight > td {
        animation: notificationRowBlink 0.4s ease-in-out 5;
    }
</style>

<script>
    (function () {
        const run = () => {
            const params = new URLSearchParams(window.location.search);
            const highlightId = params.get('highlight_id');

            if (!highlightId) {
                return;
            }

            const attemptHighlight = () => {
                const selectors = [
                    `tr[data-record-key="${CSS.escape(highlightId)}"]`,
                    `tr[data-record-id="${CSS.escape(highlightId)}"]`,
                    `tr[wire\\:key*="${CSS.escape(highlightId)}"]`,
                ];

                for (const selector of selectors) {
                    const row = document.querySelector(selector);

                    if (!row) {
                        continue;
                    }

                    row.classList.remove('notification-row-highlight');
                    void row.offsetWidth;
                    row.classList.add('notification-row-highlight');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    window.setTimeout(() => {
                        row.classList.remove('notification-row-highlight');
                    }, 2200);

                    try {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('highlight_id');
                        window.history.replaceState({}, '', url);
                    } catch (e) {
                        // ignore
                    }

                    return true;
                }

                const candidates = document.querySelectorAll('[wire\\:key]');

                for (const el of candidates) {
                    const key = el.getAttribute('wire:key') || '';

                    if (!key.includes(String(highlightId))) {
                        continue;
                    }

                    const row = el.closest('tr');

                    if (!row) {
                        continue;
                    }

                    row.classList.remove('notification-row-highlight');
                    void row.offsetWidth;
                    row.classList.add('notification-row-highlight');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    window.setTimeout(() => {
                        row.classList.remove('notification-row-highlight');
                    }, 2200);

                    try {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('highlight_id');
                        window.history.replaceState({}, '', url);
                    } catch (e) {
                        // ignore
                    }

                    return true;
                }

                return false;
            };

            let tries = 0;
            const maxTries = 40;

            const interval = window.setInterval(() => {
                tries += 1;

                if (attemptHighlight() || tries >= maxTries) {
                    window.clearInterval(interval);
                }
            }, 250);
        };

        document.addEventListener('DOMContentLoaded', run);
        document.addEventListener('livewire:navigated', run);
    })();
</script>
