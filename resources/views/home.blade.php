<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Multi Tenant CRM</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --ink: #15233b;
            --muted: #5f6f8b;
            --brand: #0b7285;
            --brand-2: #2b8a3e;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Helvetica Neue", sans-serif;
            background:
                radial-gradient(circle at 10% 10%, rgba(11,114,133,.12), transparent 40%),
                radial-gradient(circle at 90% 0%, rgba(43,138,62,.12), transparent 35%),
                var(--bg);
            color: var(--ink);
        }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 3rem 1rem; }
        .hero {
            display: grid;
            gap: 1rem;
            background: var(--card);
            border-radius: 20px;
            padding: 2.25rem;
            box-shadow: 0 20px 60px rgba(21,35,59,.08);
        }
        h1 { margin: 0; font-size: clamp(1.8rem, 4vw, 3rem); }
        p { margin: 0; color: var(--muted); line-height: 1.6; }
        .actions { margin-top: 1rem; display: flex; gap: .8rem; flex-wrap: wrap; }
        .btn {
            text-decoration: none;
            padding: .8rem 1.1rem;
            border-radius: 10px;
            font-weight: 600;
            display: inline-block;
        }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-alt { background: #e7eef7; color: var(--ink); }
    </style>
</head>
<body>
<div class="wrap">
    <section class="hero">
        <h1>Multi-Tenant CRM Platform</h1>
        <p>End-to-end hierarchy from super admin to consumers with quotations, invoices, commissions, and invitation workflows.</p>
        <div class="actions">
            <a class="btn btn-primary" href="/login">Login</a>
            <a class="btn btn-alt" href="/super-admin/login">Super Admin Panel</a>
            <a class="btn btn-alt" href="/admin/login">Tenant Panel</a>
        </div>
    </section>
</div>
</body>
</html>
