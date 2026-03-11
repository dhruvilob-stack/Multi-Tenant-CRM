<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DN – Multi-Tenant CRM Demo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
/* ─── THEME TOKENS ─────────────────────────────────────────────── */
:root {
  --bg:        #f0f4fa;
  --bg2:       #ffffff;
  --ink:       #0d1b2a;
  --muted:     #5a6a7e;
  --card:      #ffffff;
  --card2:     #f7f9fc;
  --line:      #dce4ef;
  --brand:     #0f7de8;
  --brand-d:   #0a5cbf;
  --brand2:    #00b894;
  --accent:    #f59e0b;
  --hero-bg:   #e8f1fb;
  --nav-bg:    rgba(240,244,250,0.82);
  --shadow:    rgba(13,27,42,0.10);
  --shadow-lg: rgba(13,27,42,0.16);
  --toggle-bg: #dce4ef;
  --toggle-knob: #fff;
  --code-bg:   #e4ecf7;
  --code-ink:  #0d1b2a;
}
[data-theme="dark"] {
  --bg:        #0b1120;
  --bg2:       #111827;
  --ink:       #e8f0fe;
  --muted:     #8fa3bc;
  --card:      #151f30;
  --card2:     #1a2640;
  --line:      #1e2d42;
  --brand:     #3b9eff;
  --brand-d:   #2078d4;
  --brand2:    #00d4a8;
  --accent:    #fbbf24;
  --hero-bg:   #0d1729;
  --nav-bg:    rgba(11,17,32,0.88);
  --shadow:    rgba(0,0,0,0.35);
  --shadow-lg: rgba(0,0,0,0.55);
  --toggle-bg: #1e2d42;
  --toggle-knob: #3b9eff;
  --code-bg:   #1a2640;
  --code-ink:  #93c5fd;
}

/* ─── RESET & BASE ─────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:Arial,sans-serif;
  background:var(--bg);
  color:var(--ink);
  min-height:100vh;
  transition:background .35s,color .35s;
  overflow-x:hidden;
}
a{text-decoration:none;color:inherit}
img{display:block;max-width:100%}

/* ─── GRID LINES BACKGROUND ───────────────────────────────────── */
body::before{
  content:'';
  position:fixed;
  inset:0;
  pointer-events:none;
  z-index:0;
  background-image:
    linear-gradient(var(--line) 1px, transparent 1px),
    linear-gradient(90deg, var(--line) 1px, transparent 1px);
  background-size:56px 56px;
  opacity:.35;
  transition:opacity .35s;
}
[data-theme="dark"] body::before{opacity:.18}

/* ─── NAV ──────────────────────────────────────────────────────── */
nav{
  position:sticky;top:0;z-index:100;
  display:flex;align-items:center;justify-content:space-between;
  padding:.9rem 2.5rem;
  background:var(--nav-bg);
  backdrop-filter:blur(14px);
  border-bottom:1px solid var(--line);
  transition:background .35s,border-color .35s;
}
.logo{
  display:flex;align-items:center;gap:.7rem;
  font-family:Arial,sans-serif;font-size:1.35rem;font-weight:800;
  letter-spacing:-.02em;color:var(--ink);
}
.logo-icon{
  width:36px;height:36px;
  background:linear-gradient(135deg,var(--brand),var(--brand2));
  border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 4px 14px var(--shadow);
}
.logo-icon svg{width:20px;height:20px;fill:#fff}
.nav-links{display:flex;align-items:center;gap:2rem;font-size:.9rem;font-weight:500;color:var(--muted)}
.nav-links a:hover{color:var(--brand)}
.nav-right{display:flex;align-items:center;gap:.85rem}

.cart-btn{
  position:relative;
  width:42px;height:42px;
  border-radius:12px;
  border:1.5px solid var(--line);
  background:var(--card);
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;
  box-shadow:0 6px 18px var(--shadow);
  transition:transform .15s,box-shadow .2s,border-color .2s;
}
.cart-btn:hover{transform:translateY(-2px);box-shadow:0 10px 24px var(--shadow-lg)}
.cart-btn.has-items{border-color:var(--brand)}
.cart-btn svg{width:20px;height:20px;stroke:var(--ink)}
.cart-count{
  position:absolute;top:-6px;right:-6px;
  min-width:20px;height:20px;
  border-radius:999px;
  background:linear-gradient(90deg,var(--brand),var(--brand2));
  color:#fff;font-size:.7rem;font-weight:700;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 6px 16px var(--shadow-lg);
}

/* Theme toggle */
.toggle{
  position:relative;width:52px;height:28px;cursor:pointer;
  background:var(--toggle-bg);
  border-radius:999px;
  border:1.5px solid var(--line);
  transition:background .3s,border-color .3s;
}
.toggle::after{
  content:'';
  position:absolute;top:3px;left:3px;
  width:20px;height:20px;
  border-radius:50%;
  background:var(--toggle-knob);
  box-shadow:0 2px 6px var(--shadow-lg);
  transition:transform .3s cubic-bezier(.34,1.56,.64,1),background .3s;
}
[data-theme="dark"] .toggle::after{transform:translateX(24px)}
.toggle-icons{
  position:absolute;inset:0;
  display:flex;align-items:center;justify-content:space-between;
  padding:0 5px;pointer-events:none;font-size:.75rem;
}

.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:.5rem;
  padding:.62rem 1.3rem;border-radius:10px;font-weight:600;font-size:.9rem;
  border:none;cursor:pointer;transition:transform .15s,box-shadow .15s,background .25s;
  font-family:'DM Sans',sans-serif;
}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 22px var(--shadow-lg)}
.btn-primary{background:linear-gradient(90deg,var(--brand),var(--brand-d));color:#fff}
.btn-outline{background:transparent;border:1.5px solid var(--line);color:var(--ink)}
[data-theme="dark"] .btn-outline{color:var(--ink)}

/* ─── HERO ─────────────────────────────────────────────────────── */
.hero{
  position:relative;z-index:1;
  min-height:92vh;
  display:grid;grid-template-columns:1fr 1fr;
  align-items:center;
  gap:4rem;
  padding:5rem 2.5rem 4rem;
  max-width:1240px;margin:0 auto;
}
.hero-glow{
  position:absolute;
  width:700px;height:700px;
  border-radius:50%;
  background:radial-gradient(circle,rgba(59,158,255,.18),transparent 65%);
  top:-10%;right:-8%;
  pointer-events:none;z-index:0;
  transition:background .35s;
}
[data-theme="dark"] .hero-glow{background:radial-gradient(circle,rgba(59,158,255,.13),transparent 65%)}

.hero-left{position:relative;z-index:2}
.hero-eyebrow{
  display:inline-flex;align-items:center;gap:.5rem;
  padding:.3rem .85rem;border-radius:999px;
  background:linear-gradient(90deg,rgba(15,125,232,.12),rgba(0,184,148,.12));
  border:1px solid rgba(15,125,232,.22);
  font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  color:var(--brand);margin-bottom:1.4rem;
}
.hero-eyebrow span{width:7px;height:7px;border-radius:50%;background:var(--brand2);animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(.8)}}

h1.hero-title{
  font-family:Arial,sans-serif;
  font-size:clamp(2.2rem,4.5vw,3.6rem);
  font-weight:800;
  line-height:1.1;
  letter-spacing:-.03em;
  color:var(--ink);
  margin-bottom:1.2rem;
}
h1.hero-title em{
  font-style:normal;
  background:linear-gradient(90deg,var(--brand),var(--brand2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  background-clip:text;
}
.hero-sub{
  font-size:1.05rem;line-height:1.75;color:var(--muted);
  max-width:500px;margin-bottom:2rem;
}
.hero-cta{display:flex;gap:.85rem;flex-wrap:wrap}

/* ─── CART DRAWER ─────────────────────────────────────────────── */
.cart-overlay{
  position:fixed;inset:0;z-index:180;
  background:rgba(13,27,42,.35);
  opacity:0;pointer-events:none;
  transition:opacity .2s ease;
}
.cart-overlay.show{opacity:1;pointer-events:auto}
.cart-panel{
  position:fixed;top:0;right:0;z-index:190;
  width:min(420px,90vw);height:100vh;
  background:var(--card);
  border-left:1px solid var(--line);
  box-shadow:-14px 0 30px var(--shadow-lg);
  transform:translateX(100%);
  transition:transform .25s ease;
  display:flex;flex-direction:column;
}
.cart-panel.open{transform:translateX(0)}
.cart-head{
  padding:1.4rem 1.6rem;border-bottom:1px solid var(--line);
  display:flex;align-items:center;justify-content:space-between;
}
.cart-title{font-weight:800;font-size:1.05rem}
.cart-close{
  border:none;background:transparent;color:var(--muted);
  font-size:1.2rem;cursor:pointer;
}
.cart-body{padding:1.4rem 1.6rem;display:flex;flex-direction:column;gap:1rem}
.cart-card{
  background:var(--card2);border:1px solid var(--line);
  border-radius:16px;padding:1.2rem;
}
.cart-card h4{font-size:1.05rem;margin-bottom:.4rem}
.cart-price{font-size:1.6rem;font-weight:800;color:var(--ink)}
.cart-meta{font-size:.9rem;color:var(--muted);margin-top:.4rem}
.cart-action{margin-top:1rem}

/* MERCHANT REGISTER CARD */
.register-card{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:20px;
  padding:2rem;
  box-shadow:0 24px 64px var(--shadow);
  transition:background .35s,border-color .35s,box-shadow .35s;
}
.register-card h2{
  font-family:Arial,sans-serif;font-size:1.6rem;font-weight:800;
  margin-bottom:.5rem;color:var(--ink);
}
.register-card p{font-size:.93rem;color:var(--muted);line-height:1.65;margin-bottom:1.5rem}
.register-card .btn{width:100%;margin-bottom:1rem}

/* FEATURE MINI CARDS */
.feat-grid{
  display:grid;grid-template-columns:1fr 1fr;gap:.8rem;
  margin-top:.8rem;
}
.feat-mini{
  background:var(--card2);
  border:1px solid var(--line);
  border-radius:14px;
  padding:.9rem;
  transition:background .35s,border-color .35s,transform .2s,box-shadow .2s;
}
.feat-mini:hover{transform:translateY(-3px);box-shadow:0 10px 28px var(--shadow)}
.feat-mini-icon{
  width:36px;height:36px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  margin-bottom:.6rem;
  background:linear-gradient(135deg,rgba(15,125,232,.15),rgba(0,184,148,.1));
}
.feat-mini-icon svg{width:18px;height:18px;stroke:var(--brand);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.feat-mini h4{font-size:.88rem;font-weight:700;margin-bottom:.3rem;color:var(--ink)}
.feat-mini p{font-size:.78rem;color:var(--muted);line-height:1.55}

/* HERO DIAGRAM (top right) */
.hero-diagram{
  position:absolute;top:-2rem;right:-1rem;
  width:260px;opacity:.55;pointer-events:none;
  transition:opacity .35s;
}
[data-theme="dark"] .hero-diagram{opacity:.3}

/* ─── SECTION WRAPPER ──────────────────────────────────────────── */
.section-wrap{
  position:relative;z-index:1;
  max-width:1240px;margin:0 auto;padding:0 2.5rem 5rem;
}
.section-label{
  display:inline-block;
  font-size:.76rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  color:var(--brand);margin-bottom:.8rem;
}
.section-title{
  font-family:Arial,sans-serif;
  font-size:clamp(1.7rem,3vw,2.4rem);
  font-weight:800;letter-spacing:-.03em;color:var(--ink);
  margin-bottom:.7rem;
}
.section-sub{font-size:.98rem;color:var(--muted);line-height:1.7;max-width:580px;margin-bottom:2.5rem}

/* ─── STATS STRIP ──────────────────────────────────────────────── */
.stats-strip{
  display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:5rem;
}
.stat-card{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:16px;
  padding:1.4rem 1.2rem;
  text-align:center;
  transition:background .35s,border-color .35s,transform .2s,box-shadow .2s;
}
.stat-card:hover{transform:translateY(-4px);box-shadow:0 14px 36px var(--shadow)}
.stat-card strong{
  display:block;
  font-family:Arial,sans-serif;font-size:1.6rem;font-weight:800;
  background:linear-gradient(90deg,var(--brand),var(--brand2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  margin-bottom:.3rem;
}
.stat-card span{font-size:.84rem;color:var(--muted)}

/* ─── FEATURES GRID ────────────────────────────────────────────── */
.features-grid{
  display:grid;grid-template-columns:repeat(3,1fr);gap:1.1rem;margin-bottom:5rem;
}
.feature-card{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:18px;
  padding:1.4rem;
  transition:background .35s,border-color .35s,transform .2s,box-shadow .2s;
}
.feature-card:hover{transform:translateY(-4px);box-shadow:0 16px 44px var(--shadow)}
.feature-icon{
  width:44px;height:44px;border-radius:13px;
  background:linear-gradient(135deg,rgba(15,125,232,.15),rgba(0,184,148,.1));
  display:flex;align-items:center;justify-content:center;
  margin-bottom:1rem;
}
.feature-icon svg{width:22px;height:22px;stroke:var(--brand);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.feature-card h3{font-family:Arial,sans-serif;font-size:1.02rem;font-weight:700;margin-bottom:.5rem;color:var(--ink)}
.feature-card p{font-size:.87rem;color:var(--muted);line-height:1.65}

/* ─── SNAPSHOTS ────────────────────────────────────────────────── */
.snapshots-grid{
  display:grid;grid-template-columns:repeat(3,1fr);gap:1.1rem;margin-bottom:2rem;
}
.snap-card{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:18px;overflow:hidden;
  transition:background .35s,border-color .35s,transform .2s,box-shadow .2s;
}
.snap-card:hover{transform:translateY(-4px);box-shadow:0 16px 44px var(--shadow)}
.snap-head{
  display:flex;justify-content:space-between;align-items:center;
  padding:.7rem 1rem;
  border-bottom:1px solid var(--line);
  background:var(--card2);
  transition:background .35s;
}
.snap-head strong{font-size:.82rem;font-weight:700;color:var(--ink)}
.snap-head span{font-size:.74rem;color:var(--brand);font-weight:600}
.snap-body{
  background:linear-gradient(160deg,var(--bg2),var(--card2));
  padding:.35rem;
  /* height:165px; */
  display:flex;flex-direction:column;justify-content:center;gap:.55rem;
  overflow:hidden;
}
.snap-body img{
  width:100%;
  height:100%;
  object-fit:cover;
  border-radius:8px;
  border:5px solid var(--line);
  background:#0b1120;
  /* transform:scale(1.18); */
  transform-origin:center center;
  transition:transform .35s ease,transform-origin .35s ease;
}
.snap-card:hover .snap-body img{
  transform-origin:left top;
  transform:scale(1.75);
}
.snap-bar{
  height:8px;border-radius:999px;
  background:linear-gradient(90deg,var(--brand),var(--brand2));
  opacity:.55;
}
.snap-bar.s{width:35%} .snap-bar.m{width:60%} .snap-bar.l{width:85%} .snap-bar.xl{width:95%}

/* ─── ACCESS PANEL ─────────────────────────────────────────────── */
.access-panel{
  background:linear-gradient(140deg,var(--card),var(--card2));
  border:1px solid var(--line);
  border-radius:20px;
  padding:2rem 2rem 2rem 2.5rem;
  display:grid;grid-template-columns:1fr auto;gap:2rem;align-items:center;
  margin-bottom:5rem;
  transition:background .35s,border-color .35s;
}
.access-info h3{font-family:Arial,sans-serif;font-size:1.3rem;font-weight:800;color:var(--ink);margin-bottom:.5rem}
.access-info p{font-size:.9rem;color:var(--muted);line-height:1.6}
.access-codes{display:flex;flex-direction:column;gap:.5rem;align-items:flex-end}
code{
  font-family:'Courier New',Consolas,monospace;
  background:var(--code-bg);color:var(--code-ink);
  padding:.25rem .65rem;border-radius:8px;font-size:.82rem;
  transition:background .35s,color .35s;
}
.access-btns{display:flex;gap:.8rem;margin-top:1.2rem}

/* ─── FOOTER ───────────────────────────────────────────────────── */
footer{
  position:relative;z-index:1;
  border-top:1px solid var(--line);
  padding:1.8rem 2.5rem;
  display:flex;justify-content:space-between;align-items:center;
  background:var(--bg2);
  transition:background .35s,border-color .35s;
  font-size:.84rem;color:var(--muted);
}

/* ─── RESPONSIVE ───────────────────────────────────────────────── */
@media(max-width:980px){
  .hero{grid-template-columns:1fr;gap:3rem;padding:4rem 1.5rem 3rem}
  .stats-strip,.features-grid,.snapshots-grid{grid-template-columns:repeat(2,1fr)}
  .access-panel{grid-template-columns:1fr}
  .access-codes{align-items:flex-start}
  .nav-links{display:none}
  .section-wrap{padding:0 1.5rem 4rem}
}
@media(max-width:600px){
  .stats-strip,.features-grid,.snapshots-grid,.feat-grid{grid-template-columns:1fr}
  nav{padding:.9rem 1.2rem}
  .hero-cta{flex-direction:column}
}

/* ─── ENTRANCE ANIMATIONS ──────────────────────────────────────── */
@keyframes fadeUp{from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}
.hero-left{animation:fadeUp .7s ease both}
.register-card{animation:fadeUp .7s .15s ease both}
.stat-card:nth-child(1){animation:fadeUp .6s .05s ease both}
.stat-card:nth-child(2){animation:fadeUp .6s .12s ease both}
.stat-card:nth-child(3){animation:fadeUp .6s .19s ease both}
.stat-card:nth-child(4){animation:fadeUp .6s .26s ease both}

</style>
</head>
<body>

<!-- ── NAV ───────────────────────────────────────────────────────── -->
<nav>
  <div class="logo">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    DN - CRM
  </div>
  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#platform">Platform</a>
    <a href="#snapshots">UI Snapshots</a>
    <a href="#access">Access</a>
  </div>
  <div class="nav-right">
    <div class="toggle" id="themeToggle" title="Toggle dark / light mode" role="button" tabindex="0" aria-label="Toggle theme">
      <div class="toggle-icons"><span>☀️</span><span>🌙</span></div>
    </div>
    <button class="cart-btn" id="cartButton" aria-label="Open cart">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 6h15l-1.5 9h-12z"/>
        <path d="M6 6l-1-3H2"/>
        <circle cx="9" cy="20" r="1.5"/>
        <circle cx="18" cy="20" r="1.5"/>
      </svg>
      <span class="cart-count" id="cartCount">0</span>
    </button>
  </div>
</nav>

<!-- ── HERO ──────────────────────────────────────────────────────── -->
<div style="position:relative;overflow:hidden">
  <div class="hero-glow"></div>
  <section class="hero">
    <div class="hero-left">
      <div class="hero-eyebrow"><span></span>Filament-Powered Multi-Tenant SaaS CRM</div>
      <h1 class="hero-title">

        <em>Multiple Tenants - Control</em><br>
        in One Platform
      </h1>
      <p class="hero-sub">
        Launch your store in minutes — no servers, no stress. Isolated tenant databases, role-based operations,
        quote-to-invoice workflows, and a unified control panel that scales with your business.
      </p>
      <div class="hero-cta">
        <button type="button" class="btn btn-primary" id="buyNowBtn">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
         Buy Now
        </button>
        <a href="/demo-dn-crm" class="btn btn-outline" target="_blank" rel="noopener">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10A15.3 15.3 0 0 1 12 2z"/></svg>
          Live Demo  <svg xmlns="http://www.w3.org/2000/svg" 
     width="18" 
     height="18" 
     viewBox="0 0 24 24" 
     fill="none" 
     stroke="white" 
     stroke-width="2.8" 
     stroke-linecap="round" 
     stroke-linejoin="round">

  <!-- Arrow head -->
  <path d="M9 5H19V15" />

  <!-- Diagonal line -->
  <path d="M19 5L5 19" />

</svg>
        </a>
      </div>
    </div>

    <div>
      <!-- MERCHANT REGISTER CARD -->
      <div class="register-card">
        <h2>Merchant Registration</h2>
        <p>Launch your store in minutes — no servers, no stress.<br>Just sign up, upload your products, and start selling!</p>
        <a href="#access" class="btn btn-primary">Register Now</a>

        <!-- 4 feature mini cards matching screenshot -->
        <div class="feat-grid">
          <div class="feat-mini">
            <div class="feat-mini-icon">
              <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </div>
            <h4>Dedicated CRM Workspace</h4>
            <p>Get your own secure CRM environment with a custom domain.</p>
          </div>
          <div class="feat-mini">
            <div class="feat-mini-icon">
              <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <h4>Role & Permission Control</h4>
            <p>Define team roles and assign custom access levels.</p>
          </div>
          <div class="feat-mini">
            <div class="feat-mini-icon">
              <svg viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h8M4 18h8"/><rect x="14" y="13" width="8" height="8" rx="1"/></svg>
            </div>
            <h4>Multi-Language & RTL Support</h4>
            <p>Go global with support for multiple locales and RTL languages.</p>
          </div>
          <div class="feat-mini">
            <div class="feat-mini-icon">
              <svg viewBox="0 0 24 24"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
            </div>
            <h4>Sales & Lead Tracking</h4>
            <p>Track, manage, and convert leads with ease in a centralized dashboard.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- ── STATS ──────────────────────────────────────────────────────── -->
<div class="section-wrap" id="platform">
  <div class="section-label">Platform Impact</div>
  <div class="section-title">Why this architecture wins</div>
  <p class="section-sub">
    One codebase manages many organizations while each tenant keeps its own database boundaries and workflow lifecycle.
    Super-admin controls onboarding, tenant health, and governance without cross-tenant data mixing.
  </p>
  <div class="stats-strip">
    <div class="stat-card"><strong>Separate DBs</strong><span>Per-tenant isolation for security and scalability.</span></div>
    <div class="stat-card"><strong>Role Flows</strong><span>Org Admin to Consumer with structured permissions.</span></div>
    <div class="stat-card"><strong>Unified Ops</strong><span>CRM, inventory, invoicing, mail, and audit in one stack.</span></div>
    <div class="stat-card"><strong>Filament UI</strong><span>Fast admin productivity and easy panel extensibility.</span></div>
  </div>

  <!-- ── FEATURES ───────────────────────────────────────────────── -->
  <div id="features">
    <div class="section-label">Core Features</div>
    <div class="section-title">What this system demonstrates</div>
    <p class="section-sub">Every module is purpose-built for multi-tenant SaaS with clean role boundaries and workflow isolation.</p>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></div>
        <h3>Tenant Lifecycle Management</h3>
        <p>Create, update, activate/suspend, and monitor tenants from super-admin with route-based slug login.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg></div>
        <h3>Sales Pipeline</h3>
        <p>Products, quotations, invoices, orders, approvals, and PDF generation integrated in tenant workflow.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
        <h3>Collaboration Mailbox</h3>
        <p>Inbox, Starred, Sent, Trash, Templates and quick compose for role-focused communication handling.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <h3>Commission + Finance</h3>
        <p>Margin commission, payout ledger, partner wallets, and transaction visibility through audit trails.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></div>
        <h3>Audit and Notifications</h3>
        <p>Action-level history and system notifications for accountability in both tenant and super-admin context.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M5.34 18.66l-1.41 1.41M20 12h2M2 12h2m15.07 7.07l-1.41-1.41M5.34 5.34 3.93 3.93M12 20v2M12 2v2"/></svg></div>
        <h3>Customizable Navigation</h3>
        <p>Filament-based panel navigation order customization and role-specific operational views.</p>
      </div>
    </div>
  </div>

  <!-- ── SNAPSHOTS ──────────────────────────────────────────────── -->
  <div id="snapshots">
    <div class="section-label">Demo Snapshots</div>
    <div class="section-title">UI at a glance</div>
    <p class="section-sub">Explore Demo Snapshots.</p>
    <div class="snapshots-grid">
      <div class="snap-card">
        <div class="snap-head"><strong>Super Admin Control Center</strong><span>Tenants + Governance</span></div>
        <div class="snap-body">
          <img src="{{ route('demo.image', ['file' => '1.png']) }}" alt="Super Admin Control Center">
        </div>
      </div>
      <div class="snap-card">
        <div class="snap-head"><strong>Tenant CRM Workspace</strong><span>Organization Admn Workspace</span></div>
        <div class="snap-body">
          <img src="{{ route('demo.image', ['file' => '2.png']) }}" alt="Tenant CRM Workspace">
        </div>
      </div>
      <div class="snap-card">
        <div class="snap-head"><strong>Mail + Activity Monitor</strong><span>Team Communication</span></div>
        <div class="snap-body">
          <img src="{{ route('demo.image', ['file' => '3.png']) }}" alt="Mail and Activity Monitor">
        </div>
      </div>
    </div>
  </div>

  <!-- ── ACCESS PANEL ───────────────────────────────────────────── -->
  {{-- <div id="access">
    <div class="section-label">Quick Access</div>
    <div class="section-title">Demo Login Links</div>
    <div class="access-panel">
      <div class="access-info">
        <h3>Ready for live demo sharing</h3>
        <p>
          Use the routes below to log into your super admin or any tenant panel.
          Tenant DB pattern: <code>tenant_{slug}</code>. Example tenant: <code>zenithbridge</code>.
        </p>
        <div class="access-btns">
          <a href="/super-admin/login" class="btn btn-primary">Super Admin Login</a>
          <a href="/zenithbridge/login" class="btn btn-outline">ZenithBridge Tenant</a>
        </div>
      </div>
      <div class="access-codes">
        <code>/super-admin/login</code>
        <code>/{slug}/login</code>
        <code>/zenithbridge/login</code>
        <code>tenant_{slug} (DB pattern)</code>
      </div>
    </div>
  </div> --}}
</div>

<div class="cart-overlay" id="cartOverlay"></div>
<aside class="cart-panel" id="cartPanel" aria-hidden="true">
  <div class="cart-head">
    <div class="cart-title">Your Cart</div>
    <button class="cart-close" id="cartClose" aria-label="Close cart">×</button>
  </div>
  <div class="cart-body">
    <div class="cart-card">
      <h4>Multi Tenant CRM System</h4>
      <div class="cart-price">$899</div>
      <div class="cart-meta">Full source code, multi-tenant SaaS, Filament admin, workflows, invoices.</div>
      <div class="cart-action">
        <button class="btn btn-primary" type="button">Buy Now</button>
      </div>
    </div>
  </div>
</aside>

<!-- ── FOOTER ─────────────────────────────────────────────────────── -->
<footer>
  <div class="logo" style="font-size:1rem">
    <div class="logo-icon" style="width:28px;height:28px;border-radius:8px">
      <svg viewBox="0 0 24 24" style="width:15px;height:15px;fill:#fff"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    DN CRM
  </div>
  <span>Multi-Tenant SaaS Platform</span>
  <span>© 2026 Dhruvil Nakrani. All rights reserved.</span>
</footer>

<script>
const toggle = document.getElementById('themeToggle');
const html = document.documentElement;

// Init from localStorage
const saved = localStorage.getItem('DN-theme');
if (saved) html.setAttribute('data-theme', saved);

toggle.addEventListener('click', () => {
  const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  localStorage.setItem('DN-theme', next);
});
toggle.addEventListener('keydown', e => {
  if (e.key === 'Enter' || e.key === ' ') toggle.click();
});

const cartButton = document.getElementById('cartButton');
const cartCount = document.getElementById('cartCount');
const cartPanel = document.getElementById('cartPanel');
const cartOverlay = document.getElementById('cartOverlay');
const cartClose = document.getElementById('cartClose');
const buyNowBtn = document.getElementById('buyNowBtn');

const openCart = () => {
  cartPanel.classList.add('open');
  cartOverlay.classList.add('show');
  cartPanel.setAttribute('aria-hidden', 'false');
};
const closeCart = () => {
  cartPanel.classList.remove('open');
  cartOverlay.classList.remove('show');
  cartPanel.setAttribute('aria-hidden', 'true');
};

cartButton.addEventListener('click', openCart);
cartOverlay.addEventListener('click', closeCart);
cartClose.addEventListener('click', closeCart);

buyNowBtn.addEventListener('click', () => {
  cartCount.textContent = '1';
  cartButton.classList.add('has-items');
  openCart();
});
</script>
</body>
</html>
