<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tenant Dashboard</title>
    <style>
        html, body { margin:0; height:100%; font-family: "Segoe UI", sans-serif; background:#f8fafc; }
        .top { display:flex; justify-content:space-between; align-items:center; padding:.65rem 1rem; background:#0f766e; color:#fff; }
        .meta { font-size:.92rem; }
        .open { color:#fff; text-decoration:none; border:1px solid rgba(255,255,255,.5); padding:.35rem .6rem; border-radius:8px; }
        iframe { border:0; width:100%; height:calc(100% - 52px); background:#fff; }
    </style>
</head>
<body>
<div class="top">
    <div class="meta">Tenant: {{ $tenant }} | Role: {{ $role ?? 'tenant_user' }}</div>
    <a class="open" href="/admin" target="_blank" rel="noreferrer">Open Panel In New Tab</a>
</div>
<iframe src="/admin" title="Tenant Panel"></iframe>
</body>
</html>
