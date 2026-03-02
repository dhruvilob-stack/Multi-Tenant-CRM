<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password</title>
    <style>
        body { margin:0; font-family: "Segoe UI", sans-serif; background:#f4f6fb; }
        .box { max-width:420px; margin:8vh auto; background:#fff; padding:2rem; border-radius:16px; box-shadow:0 12px 40px rgba(0,0,0,.08);}
        input { width:100%; padding:.75rem; border:1px solid #cbd5e1; border-radius:10px; }
        button { width:100%; margin-top:1rem; padding:.8rem; border:0; border-radius:10px; background:#2563eb; color:#fff; font-weight:700; }
        .ok { color:#166534; font-size:.9rem; }
        .err { color:#b91c1c; font-size:.9rem; }
    </style>
</head>
<body>
<div class="box">
    <h1>Forgot Password</h1>
    @if (session('status')) <div class="ok">{{ session('status') }}</div> @endif
    <form method="post" action="/forgot-password">
        @csrf
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <button type="submit">Send reset link</button>
    </form>
</div>
</body>
</html>
