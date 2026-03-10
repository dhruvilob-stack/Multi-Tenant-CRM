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

        const dispatchComposerPrefill = (payload = {}) => {
            if (!payload || (!payload.to && !payload.subject && !payload.body)) {
                return;
            }

            const send = () => {
                if (!window.Livewire?.dispatch) return;

                if (window.Livewire.dispatchTo) {
                    window.Livewire.dispatchTo('mail-composer-panel', 'mail-compose-prefill', payload);
                }

                window.Livewire.dispatch('mail-compose-prefill', payload);
            };

            // Retry once because the composer can be opening right when reply is clicked.
            send();
            setTimeout(send, 120);
        };

        const openComposer = (payload = {}) => {
            setOpen(true);
            setMinimized(false);
            restorePosition();

            if (window.Livewire?.dispatch) {
                if (payload?.fresh !== false) {
                    window.Livewire.dispatch('mail-compose-reset');
                }

                dispatchComposerPrefill(payload);
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
        window.addEventListener('mail-compose-sent', () => closeComposer());
        document.addEventListener('mail-compose-sent', () => closeComposer());
        document.addEventListener('DOMContentLoaded', sync);
        document.addEventListener('livewire:navigated', sync);

        const decodeSuggestionText = (html) => {
            try {
                const div = document.createElement('div');
                div.innerHTML = String(html || '');
                return (div.textContent || '').replace(/\s+/g, ' ').trim();
            } catch (_) {
                return '';
            }
        };

        const getCsrfToken = () =>
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const resolveTenantPrefix = () => {
            const path = String(window.location.pathname || '/');
            const parts = path.split('/').filter(Boolean);
            // Tenant is always first path segment in this app: /{tenant}/...
            const tenant = parts[0] || '';
            return tenant ? `/${tenant}` : '';
        };

        const findComposerRoot = () => win.querySelector('[wire\\:id]') || win;

        const findEditorEl = () => {
            const rootEl = findComposerRoot();
            return rootEl.querySelector('.fi-fo-rich-editor .tiptap') || rootEl.querySelector('.tiptap');
        };

        const findToInput = () => win.querySelector('input[wire\\:model\\.defer=\"to\"]');
        const findSubjectInput = () => win.querySelector('input[wire\\:model\\.defer=\"subject\"]');

        const ensureGhost = () => {
            let ghost = win.querySelector('#mail-ai-ghost');
            if (ghost) return ghost;
            ghost = document.createElement('span');
            ghost.id = 'mail-ai-ghost';
            ghost.style.position = 'fixed';
            ghost.style.pointerEvents = 'none';
            ghost.style.zIndex = '9999';
            ghost.style.color = 'rgba(107, 114, 128, 0.85)';
            ghost.style.fontSize = '12px';
            ghost.style.background = 'transparent';
            ghost.style.whiteSpace = 'pre';
            ghost.style.display = 'none';
            document.body.appendChild(ghost);
            return ghost;
        };

        const placeGhostAtCaret = (ghost, text) => {
            if (!ghost) return;
            const sel = window.getSelection?.();
            if (!sel || sel.rangeCount === 0) return;
            const range = sel.getRangeAt(0).cloneRange();
            range.collapse(true);

            const rect = range.getBoundingClientRect();
            if (!rect || (!rect.left && !rect.top && !rect.width && !rect.height)) {
                ghost.style.display = 'none';
                return;
            }

            ghost.textContent = text;
            ghost.style.left = Math.max(8, rect.left + 2) + 'px';
            ghost.style.top = Math.max(8, rect.top + 2) + 'px';
            ghost.style.display = text ? 'block' : 'none';
        };

        const insertTextAtCaret = (text) => {
            const el = findEditorEl();
            if (!el) return false;
            el.focus();

            // Prefer execCommand for rich editors that intercept input.
            if (document.queryCommandSupported?.('insertText')) {
                return document.execCommand('insertText', false, text);
            }

            try {
                const sel = window.getSelection?.();
                if (!sel || sel.rangeCount === 0) return false;
                const range = sel.getRangeAt(0);
                range.deleteContents();
                range.insertNode(document.createTextNode(text));
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
                return true;
            } catch (_) {
                return false;
            }
        };

        let aiTimer = null;
        let aiAbort = null;
        let aiSuggestion = '';
        let aiLastInput = '';
        let aiBusy = false;

        const requestAutocomplete = async () => {
            const editor = findEditorEl();
            if (!editor) return;

            const currentText = (editor.innerText || '').replace(/\s+/g, ' ').trim();
            if (currentText.length < 10) {
                aiSuggestion = '';
                return;
            }

            // Avoid repeated calls for identical input.
            if (currentText === aiLastInput) return;
            aiLastInput = currentText;

            // Only suggest after user finishes a word.
            const lastChar = currentText.slice(-1);
            if (!/[a-z0-9\.\,\!\?\)]/i.test(lastChar)) {
                return;
            }

            if (aiAbort) {
                try { aiAbort.abort(); } catch (_) {}
            }
            aiAbort = new AbortController();
            aiBusy = true;

            const to = (findToInput()?.value || '').trim();
            const subject = (findSubjectInput()?.value || '').trim();
            const body = currentText.slice(-900); // send tail for continuation

            try {
                const tenantPrefix = resolveTenantPrefix();
                if (!tenantPrefix) return;

                const res = await fetch(`${tenantPrefix}/mail/compose/assist`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        to,
                        subject,
                        body,
                        mode: 'autocomplete',
                    }),
                    signal: aiAbort.signal,
                });

                if (!res.ok) {
                    aiSuggestion = '';
                    return;
                }
                const json = await res.json();
                const html = json?.suggestion_html || '';
                const suggestionText = decodeSuggestionText(html);
                aiSuggestion = suggestionText;
            } catch (_) {
                // ignore
            } finally {
                aiBusy = false;
            }
        };

        const scheduleAutocomplete = () => {
            if (aiTimer) clearTimeout(aiTimer);
            aiTimer = setTimeout(requestAutocomplete, 650);
        };

        const bootInlineAi = () => {
            const editor = findEditorEl();
            if (!editor) return;
            if (editor.dataset.aiInlineBound === '1') return;
            editor.dataset.aiInlineBound = '1';

            const ghost = ensureGhost();

            const refreshGhost = () => {
                if (!aiSuggestion) {
                    ghost.style.display = 'none';
                    return;
                }

                // Only show first "word" suggestion.
                const firstWord = aiSuggestion.split(/\s+/).slice(0, 1).join(' ').trim();
                placeGhostAtCaret(ghost, firstWord ? ` ${firstWord}` : '');
            };

            editor.addEventListener('input', () => {
                aiSuggestion = '';
                refreshGhost();
                scheduleAutocomplete();
            });

            editor.addEventListener('keyup', () => {
                refreshGhost();
            });
            editor.addEventListener('click', () => {
                refreshGhost();
            });
            document.addEventListener('selectionchange', () => {
                if (document.activeElement === editor) refreshGhost();
            });

            editor.addEventListener('keydown', (e) => {
                if (e.key !== 'Tab') return;
                if (!aiSuggestion) return;

                e.preventDefault();

                const firstWord = aiSuggestion.split(/\s+/).slice(0, 1).join(' ').trim();
                if (!firstWord) return;

                if (insertTextAtCaret(' ' + firstWord)) {
                    aiSuggestion = aiSuggestion.replace(new RegExp('^\\s*' + firstWord.replace(/[.*+?^${}()|[\\]\\\\]/g, '\\\\$&') + '\\b'), '').trim();
                    refreshGhost();
                    scheduleAutocomplete();
                }
            });

            // Initial schedule when opening composer.
            scheduleAutocomplete();
        };

        const bootInlineAiWithRetry = () => {
            bootInlineAi();
            setTimeout(bootInlineAi, 250);
            setTimeout(bootInlineAi, 900);
        };

        window.addEventListener('open-mail-composer', bootInlineAiWithRetry);
        document.addEventListener('livewire:navigated', bootInlineAiWithRetry);
    })();
</script>
