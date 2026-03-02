<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation unavailable</title>
    <style>
        body { margin:0; font-family:"Inter","Segoe UI",system-ui,sans-serif; background:#0f172a; color:#f8fafc; }
        .shell { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; }
        .card { width:min(420px,100%); background:#020617; border-radius:24px; padding:2rem; border:1px solid rgba(15, 23, 42, .6); box-shadow:0 10px 40px rgba(15,23,42,.4); }
        h1 { margin:0 0 .5rem; font-size:1.6rem; }
        p { margin:0 0 1rem; color:#a5b4fc; }
        ul { padding-left:1.1rem; margin:0 0 1rem; color:#fecdd3; }
        a { color:#38bdf8; text-decoration:none; font-weight:600; }
        .badge { display:inline-flex; align-items:center; gap:.4rem; padding:.4rem .9rem; border-radius:999px; background:#f97316; color:#fff; font-size:.85rem; }
    </style>
</head>
<body>
<div class="shell">
    <div class="card">
        <div class="badge">Invitation</div>
        <h1>Link unavailable</h1>
        <p>We couldn’t process that invitation link. It may have expired or already been accepted.</p>
        @if (!empty($errors))
            <ul>
                @foreach ($errors as $field => $messages)
                    @foreach ($messages as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                @endforeach
            </ul>
        @endif

        <p>Ask your organization admin to resend the invitation.</p>
        <a href="/admin/login">Return to login</a>
    </div>
</div>
</body>
</html>
