<style>
    @keyframes recordBlink {
        0%, 100% { background-color: transparent; }
        50% { background-color: rgba(250, 204, 21, 0.45); }
    }

    .record-blink-5 {
        animation: recordBlink .45s ease-in-out 5;
    }
</style>

<script>
    (function () {
        const getParams = () => {
            const url = new URL(window.location.href);
            return {
                id: Number(url.searchParams.get('highlight_id') || 0),
                type: (url.searchParams.get('highlight_type') || '').toLowerCase(),
            };
        };

        const findRowById = (id) => {
            const hrefNeedle = `/${id}`;
            const anchors = Array.from(document.querySelectorAll('a[href]'));
            for (const anchor of anchors) {
                const href = anchor.getAttribute('href') || '';
                if (!href.includes(hrefNeedle)) continue;
                const row = anchor.closest('tr');
                if (row) return row;
            }

            const keyed = document.querySelector(`[wire\\:key*=".${id}"]`);
            if (keyed) return keyed.closest('tr') || keyed;

            return null;
        };

        const blink = () => {
            const { id } = getParams();
            if (!id) return;

            const row = findRowById(id);
            if (!row) return;

            row.classList.add('record-blink-5');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => row.classList.remove('record-blink-5'), 2600);
        };

        document.addEventListener('DOMContentLoaded', blink);
        document.addEventListener('livewire:navigated', blink);
    })();
</script>

