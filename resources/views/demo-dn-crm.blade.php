<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DN CRM — Live Demo Access</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#eef3fb;--ink:#0d1b2a;--muted:#5a6a7e;--card:#ffffff;--line:#dce4ef;
  --brand:#0f7de8;--brand-d:#0a5cbf;--brand2:#00b894;--shadow:rgba(13,27,42,.12);
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Arial',sans-serif;background:radial-gradient(circle at 20% 20%,#ffffff, #eef3fb 55%);
  color:var(--ink);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;
}
.card{
  width:min(860px,94vw);background:var(--card);border:1px solid var(--line);
  border-radius:22px;box-shadow:0 18px 42px var(--shadow);padding:2.6rem;position:relative;overflow:hidden;
}
.card::before{
  content:'';position:absolute;right:-60px;top:-60px;width:220px;height:220px;border-radius:50%;
  background:radial-gradient(circle,rgba(15,125,232,.18),transparent 70%);
}
.badge{
  display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .8rem;border-radius:999px;
  background:linear-gradient(90deg,rgba(15,125,232,.12),rgba(0,184,148,.12));
  border:1px solid rgba(15,125,232,.2);font-weight:700;font-size:.8rem;color:var(--brand);
}
.title{font-family:'Inter',sans-serif;font-size:2.2rem;font-weight:800;margin:.8rem 0 .6rem}
.sub{color:var(--muted);line-height:1.7;max-width:560px}
.actions{display:flex;gap:1rem;margin-top:1.6rem;flex-wrap:wrap}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.75rem 1.4rem;
  border-radius:12px;font-weight:700;font-size:.95rem;border:none;cursor:pointer;text-decoration:none;
  transition:transform .15s,box-shadow .15s,background .2s;
}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 20px var(--shadow)}
.btn-primary{background:linear-gradient(90deg,var(--brand),var(--brand-d));color:#fff}
.btn-outline{background:#fff;border:1.5px solid var(--line);color:var(--ink)}
.footer{margin-top:1.2rem;color:var(--muted);font-size:.9rem}
</style>
</head>
<body>
  <div class="card">
    <span class="badge">Live Demo Access</span>
    <div class="title">DN Multi‑Tenant CRM</div>
    <p class="sub">Choose your entry point below. The super admin panel controls tenant governance, while the tenant login opens a full CRM workspace.</p>
    <div class="actions">
      <a class="btn btn-primary" href="{{ $superAdminUrl }}" target="_blank" rel="noopener">Super Admin Login</a>
      <a class="btn btn-outline" href="{{ $tenantDemoUrl }}" target="_blank" rel="noopener">Tenant Login</a>
    </div>
    <div class="footer">Do you like it?  Buy Now !<code>🔗</code></div>
  </div>
</body>
</html>
