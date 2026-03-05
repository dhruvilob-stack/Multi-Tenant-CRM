<div id="mail-composer-root" class="pointer-events-none fixed inset-0 z-[70]">
    <button
        id="mail-composer-launcher"
        type="button"
        class="pointer-events-auto fixed bottom-4 right-4 hidden rounded-full border border-gray-200 bg-white p-3 shadow-lg dark:border-white/10 dark:bg-gray-900"
        title="Open mail composer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M4 6h16v12H4z" />
            <path d="m4 7 8 6 8-6" />
        </svg>
    </button>

    <div
        id="mail-composer-window"
        class="pointer-events-auto absolute bottom-16 right-4 hidden h-[74vh] min-h-[420px] w-[94vw] max-w-5xl min-w-[420px] flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-white/10 dark:bg-gray-900"
    >
        <div id="mail-composer-drag" class="flex cursor-move items-center justify-between border-b border-gray-200 bg-gray-50 px-3 py-2 dark:border-white/10 dark:bg-white/5">
            <span class="text-sm font-medium text-gray-900 dark:text-white">New Message</span>
            <div class="flex items-center gap-2">
                <x-filament::button type="button" size="xs" color="gray" id="mail-composer-minimize">Minimize</x-filament::button>
                <x-filament::button type="button" size="xs" color="danger" id="mail-composer-close">Close</x-filament::button>
            </div>
        </div>

        <div class="flex-1 overflow-auto p-3">
            @livewire('mail-composer-panel')
        </div>

        <span id="mail-composer-resize" class="absolute bottom-0 right-0 h-3 w-3 cursor-nwse-resize"></span>
    </div>
</div>

<script>
    (function () {
        const root = document.getElementById('mail-composer-root');
        const win = document.getElementById('mail-composer-window');
        const launcher = document.getElementById('mail-composer-launcher');
        if (!root || !win || !launcher) return;

        const keys = {
            open: 'mail_compose_open_v4',
            minimized: 'mail_compose_min_v4',
            position: 'mail_compose_pos_v4',
        };

        let dragging = false;
        let resizing = false;
        let dragX = 0;
        let dragY = 0;

        const getJSON = (key, fallback = {}) => {
            try {
                const raw = localStorage.getItem(key);
                return raw ? JSON.parse(raw) : fallback;
            } catch (_) {
                return fallback;
            }
        };

        const savePosition = () => {
            localStorage.setItem(keys.position, JSON.stringify({
                left: win.style.left || '',
                top: win.style.top || '',
                width: win.style.width || '',
                height: win.style.height || '',
            }));
        };

        const restorePosition = () => {
            const p = getJSON(keys.position, {});
            if (p.left) win.style.left = p.left;
            if (p.top) win.style.top = p.top;
            if (p.width) win.style.width = p.width;
            if (p.height) win.style.height = p.height;
            if (p.left || p.top) {
                win.style.right = 'auto';
                win.style.bottom = '4rem';
            }
        };

        const setOpen = (open) => {
            localStorage.setItem(keys.open, open ? '1' : '0');
            if (!open) {
                win.classList.add('hidden');
                launcher.classList.add('hidden');
                return;
            }

            const minimized = localStorage.getItem(keys.minimized) === '1';
            win.classList.toggle('hidden', minimized);
            launcher.classList.toggle('hidden', !minimized);
            win.classList.add('flex');
        };

        const setMinimized = (minimized) => {
            localStorage.setItem(keys.minimized, minimized ? '1' : '0');
            const isOpen = localStorage.getItem(keys.open) === '1';
            if (!isOpen) return;
            win.classList.toggle('hidden', minimized);
            launcher.classList.toggle('hidden', !minimized);
        };

        const openComposer = (payload = {}) => {
            setOpen(true);
            setMinimized(false);
            restorePosition();

            if (window.Livewire?.dispatch) {
                if (payload?.fresh !== false) {
                    window.Livewire.dispatch('mail-compose-reset');
                }

                if (payload && (payload.to || payload.subject || payload.body)) {
                    window.Livewire.dispatch('mail-compose-prefill', payload);
                }
            }
        };

        const closeComposer = () => {
            setOpen(false);
            setMinimized(false);
            if (window.Livewire?.dispatch) {
                window.Livewire.dispatch('mail-compose-reset');
            }
        };

        const sync = () => {
            restorePosition();
            const isOpen = localStorage.getItem(keys.open) === '1';
            const isMin = localStorage.getItem(keys.minimized) === '1';
            if (!isOpen) {
                win.classList.add('hidden');
                launcher.classList.add('hidden');
                return;
            }
            setOpen(true);
            setMinimized(isMin);
        };

        document.getElementById('mail-composer-close')?.addEventListener('click', closeComposer);
        document.getElementById('mail-composer-minimize')?.addEventListener('click', () => setMinimized(true));
        launcher.addEventListener('click', () => {
            setOpen(true);
            setMinimized(false);
            if (window.Livewire?.dispatch) {
                window.Livewire.dispatch('mail-compose-reset');
            }
        });

        const dragHandle = document.getElementById('mail-composer-drag');
        const resizeHandle = document.getElementById('mail-composer-resize');

        dragHandle?.addEventListener('mousedown', (e) => {
            dragging = true;
            const rect = win.getBoundingClientRect();
            dragX = e.clientX - rect.left;
            dragY = e.clientY - rect.top;
            win.style.right = 'auto';
            e.preventDefault();
        });

        resizeHandle?.addEventListener('mousedown', (e) => {
            resizing = true;
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (dragging) {
                win.style.left = Math.max(0, e.clientX - dragX) + 'px';
                win.style.top = Math.max(0, e.clientY - dragY) + 'px';
            }
            if (resizing) {
                const rect = win.getBoundingClientRect();
                win.style.width = Math.max(420, e.clientX - rect.left) + 'px';
                win.style.height = Math.max(420, e.clientY - rect.top) + 'px';
            }
        });

        document.addEventListener('mouseup', () => {
            if (dragging || resizing) savePosition();
            dragging = false;
            resizing = false;
        });

        const normalizeDetail = (detail) => {
            if (Array.isArray(detail)) {
                return detail[0] || {};
            }
            if (detail && typeof detail === 'object' && Array.isArray(detail[0])) {
                return detail[0] || {};
            }
            if (detail && typeof detail === 'object' && detail.detail) {
                return normalizeDetail(detail.detail);
            }
            return detail || {};
        };

        window.addEventListener('open-mail-composer', (e) => openComposer(normalizeDetail(e.detail)));
        document.addEventListener('open-mail-composer', (e) => openComposer(normalizeDetail(e.detail)));
        document.addEventListener('DOMContentLoaded', sync);
        document.addEventListener('livewire:navigated', sync);
    })();
</script>
