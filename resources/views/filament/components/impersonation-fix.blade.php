<script>
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        if (params.get('impersonated') === '1') {
            const tenant = window.location.pathname.split('/')[1] || '';
            const role = params.get('role');

            if (tenant && role) {
                history.replaceState({}, '', `/${tenant}/${role}`);
            } else if (tenant) {
                history.replaceState({}, '', `/${tenant}`);
            }

            const cleanUrl = window.location.origin + window.location.pathname;
            history.replaceState({}, '', cleanUrl);
        }

        if (window.Livewire) {
            window.Livewire.onError(status => {
                if (status === 419) {
                    window.location.reload();
                }
            });
        }
    });
</script>
