<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <style>
        body { margin:0; font-family: "Segoe UI", sans-serif; background:#edf2f7; }
        .box { max-width:420px; margin:6vh auto; background:#fff; padding:2rem; border-radius:16px; box-shadow:0 12px 40px rgba(0,0,0,.08);}
        h1 { margin-top:0; }
        label { display:block; margin:.75rem 0 .3rem; color:#334155; }
        input { width:100%; padding:.75rem; border:1px solid #cbd5e1; border-radius:10px; }
        button { width:100%; margin-top:1rem; padding:.8rem; border:0; border-radius:10px; background:#0f766e; color:#fff; font-weight:700; }
        .links { margin-top:.75rem; text-align:right; }
        .links a { color:#0f766e; text-decoration:none; }
        .err { color:#b91c1c; font-size:.9rem; }
        .ok { color:#166534; font-size:.9rem; }
    </style>
</head>
<body>
<div class="box">
    <h1>Sign in</h1>
    @if (session('status')) <div class="ok">{{ session('status') }}</div> @endif
    <form method="post" action="/login">
        @csrf
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', session('email')) }}" required>
        @error('email')<div class="err">{{ $message }}</div>@enderror

        <label>Password</label>
        <input type="password" name="password" required>
        @error('password')<div class="err">{{ $message }}</div>@enderror

        <button type="submit">Login</button>
    </form>
    <div class="links"><a href="/forgot-password">Forgot password?</a></div>
</div>
</body>
</html>
