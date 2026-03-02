<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    <style>
        body { margin:0; font-family: "Segoe UI", sans-serif; background:#f4f6fb; }
        .box { max-width:420px; margin:8vh auto; background:#fff; padding:2rem; border-radius:16px; box-shadow:0 12px 40px rgba(0,0,0,.08);}
        input { width:100%; padding:.75rem; border:1px solid #cbd5e1; border-radius:10px; margin-bottom:.6rem; }
        button { width:100%; margin-top:.4rem; padding:.8rem; border:0; border-radius:10px; background:#7c3aed; color:#fff; font-weight:700; }
        .err { color:#b91c1c; font-size:.9rem; }
    </style>
</head>
<body>
<div class="box">
    <h1>Reset Password</h1>
    <form method="post" action="/reset-password">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="email" name="email" value="{{ old('email', $email) }}" placeholder="Email" required>
        <input type="password" name="password" placeholder="New password" required>
        <input type="password" name="password_confirmation" placeholder="Confirm password" required>
        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif
        <button type="submit">Reset Password</button>
    </form>
</div>
</body>
</html>
