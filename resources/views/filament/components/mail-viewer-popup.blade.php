<div id="mail-viewer-root" class="pointer-events-none fixed inset-0 z-[75] hidden">
    <div class="pointer-events-auto fixed inset-0 bg-black/40" id="mail-viewer-backdrop"></div>
    <div class="pointer-events-auto fixed left-1/2 top-1/2 w-[94vw] max-w-4xl -translate-x-1/2 -translate-y-1/2 rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Mail Viewer</h3>
            <x-filament::button type="button" size="xs" color="danger" id="mail-viewer-close">Close</x-filament::button>
        </div>

        <div class="space-y-2 p-4 text-sm">
            <div><strong>Subject:</strong> <span id="mv_subject"></span></div>
            <div><strong>From:</strong> <span id="mv_from"></span></div>
            <div><strong>To:</strong> <span id="mv_to"></span></div>
            <div><strong>Sent:</strong> <span id="mv_sent_at"></span></div>
            <div id="mv_body" class="max-h-[45vh] overflow-auto rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900"></div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-4 py-3 dark:border-white/10">
            <x-filament::button type="button" size="sm" color="primary" id="mail-viewer-reply">Reply</x-filament::button>
        </div>
    </div>
</div>

<script>
    (function () {
        const root = document.getElementById('mail-viewer-root');
        if (!root) return;

        const nodes = {
            subject: document.getElementById('mv_subject'),
            from: document.getElementById('mv_from'),
            to: document.getElementById('mv_to'),
            sentAt: document.getElementById('mv_sent_at'),
            body: document.getElementById('mv_body'),
            reply: document.getElementById('mail-viewer-reply'),
            close: document.getElementById('mail-viewer-close'),
            backdrop: document.getElementById('mail-viewer-backdrop'),
        };

        let currentPayload = null;

        const linkifyAndPrepareHtml = (input) => {
            const source = String(input || '');
            const hasHtml = /<[^>]+>/.test(source);

            const template = document.createElement('template');

            if (hasHtml) {
                template.innerHTML = source;
            } else {
                const escaped = source
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                template.innerHTML = escaped.replace(/\n/g, '<br>');
            }

            const urlRegex = /((?:https?:\/\/|www\.)[^\s<]+)/gi;

            const walk = (node) => {
                if (!node) return;

                if (node.nodeType === Node.TEXT_NODE) {
                    const text = node.textContent || '';
                    if (!urlRegex.test(text)) {
                        urlRegex.lastIndex = 0;
                        return;
                    }
                    urlRegex.lastIndex = 0;

                    const fragment = document.createDocumentFragment();
                    let last = 0;
                    let match;

                    while ((match = urlRegex.exec(text)) !== null) {
                        const url = match[0];
                        const start = match.index;
                        const end = start + url.length;

                        if (start > last) {
                            fragment.appendChild(document.createTextNode(text.slice(last, start)));
                        }

                        const link = document.createElement('a');
                        link.href = /^www\./i.test(url) ? `https://${url}` : url;
                        link.textContent = url;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        fragment.appendChild(link);
                        last = end;
                    }

                    if (last < text.length) {
                        fragment.appendChild(document.createTextNode(text.slice(last)));
                    }

                    node.parentNode?.replaceChild(fragment, node);
                    return;
                }

                if (node.nodeType === Node.ELEMENT_NODE) {
                    const tagName = (node.tagName || '').toUpperCase();
                    if (tagName === 'A' || tagName === 'SCRIPT' || tagName === 'STYLE') {
                        return;
                    }

                    Array.from(node.childNodes).forEach(walk);
                }
            };

            Array.from(template.content.childNodes).forEach(walk);

            template.content.querySelectorAll('a[href]').forEach((anchor) => {
                anchor.setAttribute('target', '_blank');
                anchor.setAttribute('rel', 'noopener noreferrer');
                anchor.classList.add('text-primary-600', 'underline');
                anchor.style.cursor = 'pointer';
            });

            return template.innerHTML;
        };

        const hide = () => {
            root.classList.add('hidden');
            currentPayload = null;
        };

        const show = (payload = {}) => {
            currentPayload = payload || {};
            nodes.subject.textContent = payload.subject || '';
            nodes.from.textContent = payload.from || '-';
            nodes.to.textContent = payload.to || '-';
            nodes.sentAt.textContent = payload.sent_at || '';
            nodes.body.innerHTML = linkifyAndPrepareHtml(payload.body || '');
            root.classList.remove('hidden');
        };

        nodes.body?.addEventListener('click', (event) => {
            const anchor = event.target?.closest?.('a[href]');
            if (!anchor) return;

            event.preventDefault();
            event.stopPropagation();

            const href = anchor.getAttribute('href');
            if (!href) return;

            window.open(href, '_blank', 'noopener,noreferrer');
        });

        nodes.close?.addEventListener('click', hide);
        nodes.backdrop?.addEventListener('click', hide);
        nodes.reply?.addEventListener('click', () => {
            if (!currentPayload) return;
            window.dispatchEvent(new CustomEvent('open-mail-composer', {
                detail: {
                    to: currentPayload.reply_to || currentPayload.from || '',
                    subject: currentPayload.reply_subject || '',
                    body: currentPayload.reply_body || '',
                    fresh: false,
                },
            }));
            hide();
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

        window.addEventListener('open-mail-viewer', (e) => show(normalizeDetail(e.detail)));
        document.addEventListener('open-mail-viewer', (e) => show(normalizeDetail(e.detail)));
    })();
</script>
