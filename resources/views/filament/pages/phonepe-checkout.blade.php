<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>PhonePe Checkout — {{ config('app.name') }}</title>
    <style>
        body {
            margin:0;
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:#f8fafc;
            font-family:-apple-system,Segoe UI,system-ui,sans-serif;
        }
        .card {
            background:#fff;
            padding:2rem;
            border-radius:1rem;
            box-shadow:0 12px 40px rgba(15,23,42,.12);
            text-align:center;
            max-width:420px;
            width:100%;
        }
        .loader {
            width:72px;
            height:72px;
            margin:0 auto 1.5rem;
            border-radius:50%;
            border:6px solid rgba(15,23,42,.15);
            border-top-color:#ef4444;
            animation:spin 1s linear infinite;
        }
        @keyframes spin {to{transform:rotate(360deg);}}
        h1 {font-size:1.25rem;margin-bottom:.5rem;color:#0f172a;}
        p {color:#475569;font-size:.95rem;margin-bottom:1rem;}
        a,
        button {color:#ef4444;}
    </style>
</head>
<body>
    <div class="card">
        <div class="loader"></div>
        <h1>Redirecting to PhonePe</h1>
        <p>
            Sending you securely to PhonePe for payment of
            <strong>{{ number_format(($amount ?? 0) / 100, 2) }} {{ $currency ?? 'INR' }}</strong>.
        </p>
        <p id="status">Opening in a moment…</p>
        <a href="{{ route('filament.admin.pages.dashboard', ['tenant' => $tenant]) }}">Cancel and go back</a>
    </div>
    <script>
        (() => {
            const redirectUrl = @json($redirect);
            const countdownEl = document.getElementById('status');
            let attempts = 3;
            const tick = () => {
                if (attempts <= 0) {
                    window.location.href = redirectUrl;
                    return;
                }
                countdownEl.textContent = `Opening in ${attempts}…`;
                attempts -= 1;
                setTimeout(tick, 600);
            };
            tick();
        })();
    </script>
</body>
</html>
