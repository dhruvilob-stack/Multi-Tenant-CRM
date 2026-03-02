<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set your password</title>
    <style>
        body { margin:0; font-family: "Inter", "Segoe UI", system-ui, sans-serif; background:#eef2ff; }
        .shell { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; }
        .card { width:min(480px,100%); background:#fff; border-radius:28px; padding:2.5rem; box-shadow:0 20px 60px rgba(15,23,42,.15); }
        h1 { margin:0 0 1rem; font-size:1.75rem; color:#0f172a; }
        p { margin:0 0 .6rem; color:#475569; }
        .badge { display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .8rem; border-radius:999px; background:#e0f2fe; color:#0369a1; font-size:.85rem; font-weight:600; }
        .info { margin-bottom:1.4rem; }
        .info strong { color:#0c4a6e; }
        label { display:block; margin-top:1rem; font-size:.95rem; color:#0f172a; }
        input { width:100%; padding:.85rem; margin-top:.35rem; border-radius:12px; border:1px solid #cbd5f5; font-size:.95rem; transition:.2s; background:#f8fafc; }
        input:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.15); }
        .errors { margin-bottom:1rem; padding:1rem; border-radius:12px; background:#fee2e2; color:#b91c1c; font-size:.9rem; line-height:1.5; }
        button { width:100%; margin-top:1.5rem; border:none; border-radius:14px; padding:1rem; font-size:1rem; font-weight:700; background:#0f766e; color:#fff; cursor:pointer; transition:.2s; }
        button:hover { filter:brightness(1.05); }
        .foot { margin-top:1.5rem; font-size:.85rem; color:#94a3b8; text-align:center; }
        .kid { font-size:.8rem; color:#475569; margin-top:.5rem; }
    </style>
</head>
<body>
<div class="shell">
    <div class="card">
        <div class="info">
            <div class="badge">{{ ucfirst($invitation->role) }} Invitation</div>
            <h1>Set your password</h1>
            <p><strong>Email:</strong> {{ $invitation->invitee_email }}</p>
            <p><strong>Organization:</strong> {{ $invitation->organization?->name ?? 'Not provided' }}</p>
            <p><strong>Invited by:</strong> {{ $invitation->inviter?->name ?? 'Unknown' }} ({{ $invitation->inviter?->email ?? 'n/a' }})</p>
            <p><strong>Expires:</strong> {{ $invitation->expires_at?->toDayDateTimeString() ?? '—' }}</p>
        </div>

        @if ($errors->any())
            <div class="errors">
                <strong>Please fix the following:</strong>
                <ul style="margin:0;padding-left:1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ $formAction }}">
            @csrf
            <input type="hidden" name="name" value="{{ $invitation->invitee_email }}">

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

            <button type="submit">Join {{ $invitation->organization?->name ?? 'the organization' }}</button>
        </form>

        <div class="foot">
            Already have an account?
            <a href="/admin/login" style="color:#0f766e; text-decoration:none;">Back to login</a>
            <div class="kid">We only ask you to set a password—everything else is prefilled.</div>
        </div>
    </div>
</div>
</body>
</html>
