<div>
    @if($shouldShow)

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- GLOBAL STYLES – scoped so they don't leak into the rest of app  --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <style>
            /* ── Filament-aware CSS custom properties ─────────────────────── */
            :root {
                --sub-accent:       #6366f1;
                --sub-accent-light: #818cf8;
                --sub-accent-glow:  rgba(99,102,241,.18);
                --sub-pro-accent:   #8b5cf6;
                --sub-ent-accent:   #0ea5e9;
                --sub-radius:       1rem;
                --sub-radius-sm:    .625rem;
                --sub-transition:   .22s cubic-bezier(.4,0,.2,1);
            }

            /* Light mode surface tokens */
            .fi-modal-window,
            [data-theme="light"] .fi-modal-window {
                --sub-surface-0:   #ffffff;
                --sub-surface-1:   #f8fafc;
                --sub-surface-2:   #f1f5f9;
                --sub-border:      #e2e8f0;
                --sub-text-hi:     #0f172a;
                --sub-text-md:     #475569;
                --sub-text-lo:     #94a3b8;
                --sub-tag-bg:      #ede9fe;
                --sub-tag-text:    #5b21b6;
                --sub-card-shadow: 0 4px 24px rgba(15,23,42,.08),
                                   0 1px 4px  rgba(15,23,42,.04);
                --sub-card-hover:  0 8px 40px rgba(99,102,241,.15),
                                   0 2px 8px  rgba(15,23,42,.06);
                --sub-inr-badge:   #fef3c7;
                --sub-inr-text:    #92400e;
            }

            /* Dark mode surface tokens – Filament adds .dark class to <html> */
            .dark .fi-modal-window,
            [data-theme="dark"] .fi-modal-window {
                --sub-surface-0:   #0f172a;
                --sub-surface-1:   #1e293b;
                --sub-surface-2:   #273244;
                --sub-border:      #334155;
                --sub-text-hi:     #f1f5f9;
                --sub-text-md:     #94a3b8;
                --sub-text-lo:     #475569;
                --sub-tag-bg:      rgba(99,102,241,.18);
                --sub-tag-text:    #a5b4fc;
                --sub-card-shadow: 0 4px 24px rgba(0,0,0,.4),
                                   0 1px 4px  rgba(0,0,0,.3);
                --sub-card-hover:  0 8px 40px rgba(99,102,241,.25),
                                   0 2px 8px  rgba(0,0,0,.4);
                --sub-inr-badge:   rgba(251,191,36,.12);
                --sub-inr-text:    #fbbf24;
            }

            /* ── Modal override ─────────────────────────────────────────────── */
            .sub-modal-inner {
                padding: 0 !important;
                overflow: hidden;
                border-radius: var(--sub-radius) !important;
            }

            /* ── Header banner ─────────────────────────────────────────────── */
            .sub-header {
                position: relative;
                padding: 2.5rem 2.5rem 2rem;
                background: linear-gradient(135deg,
                    var(--sub-accent) 0%,
                    var(--sub-pro-accent) 60%,
                    var(--sub-ent-accent) 100%);
                overflow: hidden;
            }
            .sub-header::before {
                content: '';
                position: absolute; inset: 0;
                background:
                    radial-gradient(ellipse 70% 60% at 80% -20%, rgba(255,255,255,.15) 0%, transparent 70%),
                    radial-gradient(ellipse 40% 50% at 10% 110%,  rgba(255,255,255,.08) 0%, transparent 70%);
                pointer-events: none;
            }
            .sub-header-eyebrow {
                display: inline-flex; align-items: center; gap: .4rem;
                font-size: .7rem; font-weight: 700; letter-spacing: .12em;
                text-transform: uppercase; color: rgba(255,255,255,.75); margin-bottom: .75rem;
            }
            .sub-header-eyebrow svg { width: 14px; height: 14px; }
            .sub-header-title { font-size: 1.75rem; font-weight: 800; color: #ffffff; line-height: 1.2; margin: 0 0 .5rem; }
            .sub-header-sub   { font-size: .9rem; color: rgba(255,255,255,.75); margin: 0; }

            .sub-steps { display: flex; align-items: center; gap: .5rem; margin-top: 1.5rem; }
            .sub-step-pill {
                display: flex; align-items: center; gap: .4rem;
                padding: .25rem .75rem; border-radius: 9999px;
                font-size: .75rem; font-weight: 600; transition: var(--sub-transition);
            }
            .sub-step-pill.active { background: rgba(255,255,255,.25); color: #fff; }
            .sub-step-pill.done   { background: rgba(255,255,255,.12); color: rgba(255,255,255,.65); }
            .sub-step-pill.future { background: rgba(255,255,255,.06); color: rgba(255,255,255,.45); }
            .sub-step-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: .7; }
            .sub-step-sep { color: rgba(255,255,255,.3); font-size: .8rem; }

            /* ── Body ────────────────────────────────────────────────────────── */
            .sub-body { padding: 2rem 2.5rem 2.5rem; background: var(--sub-surface-0); }

            /* ── Plan grid ───────────────────────────────────────────────────── */
            .sub-plan-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; }

            .sub-plan-card {
                position: relative;
                background: var(--sub-surface-1); border: 1.5px solid var(--sub-border);
                border-radius: var(--sub-radius); padding: 1.75rem 1.5rem 1.5rem;
                cursor: pointer; transition: var(--sub-transition);
                box-shadow: var(--sub-card-shadow);
                display: flex; flex-direction: column; gap: 0; overflow: hidden;
            }
            .sub-plan-card::before {
                content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
                border-radius: var(--sub-radius) var(--sub-radius) 0 0;
                background: linear-gradient(90deg, var(--sub-accent), var(--sub-pro-accent));
                opacity: 0; transition: var(--sub-transition);
            }
            .sub-plan-card:hover { border-color: var(--sub-accent); box-shadow: var(--sub-card-hover); transform: translateY(-3px); }
            .sub-plan-card:hover::before { opacity: 1; }
            .sub-plan-card.popular { border-color: var(--sub-accent); }
            .sub-plan-card.popular::before { opacity: 1; }

            .sub-popular-badge {
                position: absolute; top: 1rem; right: 1rem;
                background: linear-gradient(135deg, var(--sub-accent), var(--sub-pro-accent));
                color: #fff; font-size: .65rem; font-weight: 700; letter-spacing: .1em;
                text-transform: uppercase; padding: .2rem .6rem; border-radius: 9999px;
            }
            .sub-discount-badge {
                position: absolute; top: 1rem; left: 1rem;
                background: linear-gradient(135deg, #16a34a, #22c55e);
                color: #ffffff; font-size: .65rem; font-weight: 700; letter-spacing: .1em;
                text-transform: uppercase; padding: .2rem .6rem; border-radius: 9999px;
            }

            .sub-plan-icon { width: 2.75rem; height: 2.75rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; font-size: 1.25rem; }
            .sub-plan-icon.starter { background: rgba(99,102,241,.12); }
            .sub-plan-icon.pro     { background: rgba(139,92,246,.12); }
            .sub-plan-icon.ent     { background: rgba(14,165,233,.12); }

            .sub-plan-name  { font-size: 1.05rem; font-weight: 700; color: var(--sub-text-hi); margin: 0 0 .15rem; }
            .sub-plan-cycle { font-size: .78rem; color: var(--sub-text-lo); margin: 0 0 1.25rem; text-transform: uppercase; letter-spacing: .07em; }

            .sub-plan-price { display: flex; align-items: baseline; gap: .25rem; margin-bottom: 1.5rem; }
            .sub-plan-price-amount   { font-size: 2rem; font-weight: 800; color: var(--sub-text-hi); line-height: 1; }
            .sub-plan-price-currency { font-size: .85rem; font-weight: 600; color: var(--sub-text-md); margin-bottom: .15rem; }
            .sub-plan-price-per      { font-size: .78rem; color: var(--sub-text-lo); margin-left: .1rem; }

            .sub-feature-list { list-style: none; margin: 0 0 1.75rem; padding: 0; display: flex; flex-direction: column; gap: .6rem; flex: 1; }
            .sub-feature-item { display: flex; align-items: center; gap: .6rem; font-size: .83rem; color: var(--sub-text-md); }
            .sub-feature-item svg { flex-shrink: 0; width: 15px; height: 15px; }
            .sub-feature-item.yes svg { color: #22c55e; }
            .sub-feature-item.no  svg { color: var(--sub-text-lo); }
            .sub-feature-item.no span { opacity: .55; }
            .sub-feature-item strong  { color: var(--sub-text-hi); font-weight: 600; }

            .sub-card-divider { border: none; border-top: 1px solid var(--sub-border); margin: 0 0 1.25rem; }

            .sub-select-btn {
                display: flex; align-items: center; justify-content: center; gap: .5rem;
                width: 100%; padding: .7rem 1rem; border-radius: var(--sub-radius-sm);
                border: 1.5px solid var(--sub-accent); background: transparent;
                color: var(--sub-accent); font-size: .875rem; font-weight: 600;
                cursor: pointer; transition: var(--sub-transition);
            }
            .sub-select-btn:hover,
            .sub-plan-card.popular .sub-select-btn { background: var(--sub-accent); color: #fff; box-shadow: 0 4px 14px var(--sub-accent-glow); }
            .sub-select-btn svg { width: 15px; height: 15px; }

            /* ── Checkout layout ─────────────────────────────────────────────── */
            .sub-checkout-grid { display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start; }
            @media (max-width: 900px) { .sub-checkout-grid { grid-template-columns: 1fr; } }

            .sub-selected-plan-bar {
                display: flex; align-items: center; justify-content: space-between;
                background: var(--sub-surface-2); border: 1px solid var(--sub-border);
                border-radius: var(--sub-radius-sm); padding: 1rem 1.25rem; margin-bottom: 1.5rem;
            }
            .sub-selected-plan-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .1em; color: var(--sub-text-lo); margin-bottom: .2rem; }
            .sub-selected-plan-name  { font-size: 1rem; font-weight: 700; color: var(--sub-text-hi); }
            .sub-selected-plan-price { font-size: .82rem; color: var(--sub-text-md); margin-top: .1rem; }

            .sub-change-btn {
                padding: .4rem .9rem; border-radius: var(--sub-radius-sm);
                border: 1px solid var(--sub-border); background: var(--sub-surface-0);
                color: var(--sub-text-md); font-size: .8rem; font-weight: 600;
                cursor: pointer; transition: var(--sub-transition);
            }
            .sub-change-btn:hover { border-color: var(--sub-accent); color: var(--sub-accent); }

            .sub-form-area   { background: var(--sub-surface-0); }
            .sub-section-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--sub-text-lo); margin: 0 0 .75rem; }

            /* ── Payment method cards ─────────────────────────────────────────── */
            .sub-pm-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: .75rem; margin-bottom: 1.5rem; }
            .sub-pm-option {
                position: relative; display: flex; flex-direction: column; align-items: center; gap: .4rem;
                padding: .85rem .5rem; border-radius: var(--sub-radius-sm);
                border: 1.5px solid var(--sub-border); background: var(--sub-surface-1);
                cursor: pointer; transition: var(--sub-transition); text-align: center; user-select: none;
            }
            .sub-pm-option:hover { border-color: var(--sub-accent); }
            .sub-pm-option.selected { border-color: var(--sub-accent); background: rgba(99,102,241,.05); }
            .dark .sub-pm-option.selected { background: rgba(99,102,241,.12); }
            .sub-pm-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
            .sub-pm-logo { font-size: 1.5rem; line-height: 1; }
            .sub-pm-name { font-size: .78rem; font-weight: 700; color: var(--sub-text-hi); }
            .sub-pm-note { font-size: .65rem; color: var(--sub-text-lo); }
            .sub-pm-check {
                position: absolute; top: .45rem; right: .45rem;
                width: 15px; height: 15px; border-radius: 50%;
                background: var(--sub-accent); display: none; align-items: center; justify-content: center;
            }
            .sub-pm-check svg { width: 8px; height: 8px; stroke: #fff; }
            .sub-pm-option.selected .sub-pm-check { display: flex; }

            /* ── Currency chips ───────────────────────────────────────────────── */
            .sub-currency-row { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
            .sub-currency-chip {
                display: inline-flex; align-items: center; gap: .3rem;
                padding: .35rem .9rem; border-radius: 9999px;
                border: 1.5px solid var(--sub-border); background: var(--sub-surface-1);
                font-size: .8rem; font-weight: 600; color: var(--sub-text-md);
                cursor: pointer; transition: var(--sub-transition); user-select: none;
            }
            .sub-currency-chip:hover:not(.locked) { border-color: var(--sub-accent); color: var(--sub-accent); }
            .sub-currency-chip.selected { border-color: var(--sub-accent); background: rgba(99,102,241,.08); color: var(--sub-accent); }
            .sub-currency-chip.locked   { opacity: .5; cursor: not-allowed; pointer-events: none; }
            .sub-currency-chip input    { display: none; }

            /* INR lock notice */
            .sub-inr-notice {
                display: flex; align-items: center; gap: .45rem;
                padding: .5rem .9rem; border-radius: var(--sub-radius-sm);
                background: var(--sub-inr-badge); color: var(--sub-inr-text);
                font-size: .76rem; font-weight: 600; margin-bottom: 1rem;
            }
            .sub-inr-notice svg { width: 13px; height: 13px; flex-shrink: 0; }

            /* ── Order summary card ───────────────────────────────────────────── */
            .sub-summary-card {
                background: var(--sub-surface-1); border: 1px solid var(--sub-border);
                border-radius: var(--sub-radius); padding: 1.5rem;
                box-shadow: var(--sub-card-shadow); position: sticky; top: 1rem;
            }
            .sub-summary-title { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--sub-text-lo); margin: 0 0 1.25rem; }

            .sub-breakdown-table { display: flex; flex-direction: column; gap: .1rem; }
            .sub-breakdown-row,
            .sub-breakdown-total {
                display: flex; justify-content: space-between; align-items: center;
                padding: .55rem 0; border-bottom: 1px solid var(--sub-border);
            }
            .sub-breakdown-total { border-bottom: none; border-top: 2px solid var(--sub-border); margin-top: .4rem; padding-top: .75rem; }
            .sub-breakdown-label { font-size: .83rem; color: var(--sub-text-md); }
            .sub-breakdown-value { font-size: .83rem; font-weight: 600; color: var(--sub-text-hi); }
            .sub-breakdown-total .sub-breakdown-label { font-size: .9rem; font-weight: 700; color: var(--sub-text-hi); }
            .sub-breakdown-total .sub-breakdown-value { font-size: 1rem; font-weight: 800; color: var(--sub-accent); }

            /* FX hint */
            .sub-fx-hint {
                display: flex; align-items: center; gap: .35rem;
                font-size: .72rem; color: var(--sub-text-lo); margin-top: .6rem;
            }
            .sub-fx-hint svg { width: 12px; height: 12px; flex-shrink: 0; }

            /* Secure badge row */
            .sub-secure-row {
                display: flex; align-items: center; gap: .5rem;
                margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--sub-border);
                color: var(--sub-text-lo); font-size: .75rem;
            }
            .sub-secure-row svg { width: 14px; height: 14px; flex-shrink: 0; }

            /* Pay button */
            .sub-pay-btn {
                display: flex; align-items: center; justify-content: center; gap: .6rem;
                width: 100%; margin-top: 1rem; padding: .85rem 1.25rem;
                border-radius: var(--sub-radius-sm); border: none;
                background: linear-gradient(135deg, var(--sub-accent), var(--sub-pro-accent));
                color: #fff; font-size: .9rem; font-weight: 700;
                cursor: pointer; transition: var(--sub-transition);
                box-shadow: 0 4px 16px var(--sub-accent-glow);
            }
            .sub-pay-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(99,102,241,.35); }
            .sub-pay-btn:active { transform: translateY(0); }

            /* Trust strip */
            .sub-trust-strip {
                display: flex; align-items: center; gap: 1rem;
                margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--sub-border);
                flex-wrap: wrap;
            }
            .sub-trust-label { font-size: .7rem; color: var(--sub-text-lo); font-weight: 600; text-transform: uppercase; letter-spacing: .08em; flex: 0 0 auto; }
            .sub-trust-badge {
                display: inline-flex; align-items: center; gap: .35rem;
                padding: .3rem .7rem; border-radius: .4rem;
                background: var(--sub-surface-2); border: 1px solid var(--sub-border);
                font-size: .73rem; font-weight: 600; color: var(--sub-text-md);
            }
            .sub-trust-badge svg { width: 13px; height: 13px; }
        </style>

        <x-filament::modal
            :id="$this->getModalId()"
            :close-button="false"
            :close-by-clicking-away="false"
            :close-by-escaping="false"
            width="7xl"
            teleport="body"
        >
            {{-- ── Gradient header ──────────────────────────────────────────── --}}
            <div class="sub-header">
                <div class="sub-header-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    Subscription Plans
                </div>

                @if($step === 'plans')
                    <h2 class="sub-header-title">Choose the right plan for your Organization</h2>
                    <p class="sub-header-sub">No hidden fees · Cancel anytime · Instant activation</p>
                @else
                    <h2 class="sub-header-title">Complete your subscription</h2>
                    <p class="sub-header-sub">Secure checkout — your data is encrypted end-to-end</p>
                @endif

                <div class="sub-steps">
                    <div class="sub-step-pill {{ $step === 'plans' ? 'active' : 'done' }}">
                        <span class="sub-step-dot"></span> Select Plan
                    </div>
                    <span class="sub-step-sep">›</span>
                    <div class="sub-step-pill {{ $step === 'checkout' ? 'active' : 'future' }}">
                        <span class="sub-step-dot"></span> Payment Details
                    </div>
                    <span class="sub-step-sep">›</span>
                    <div class="sub-step-pill future">
                        <span class="sub-step-dot"></span> Activate
                    </div>
                </div>
            </div>

            {{-- ── Body ─────────────────────────────────────────────────────── --}}
            <div class="sub-body">

                {{-- ════════════════════ STEP 1 – PLANS ════════════════════ --}}
                @if($step === 'plans')
                    <div class="sub-plan-grid">
                        @foreach($plans as $i => $plan)
                            @php
                                $icons   = ['🚀','⚡','🏢'];
                                $classes = ['starter','pro','ent'];
                                $popular = (bool) ($plan['popular'] ?? false);
                                $iconClass = $classes[$i % count($classes)];
                                $discountLabel = trim((string) ($plan['discount_label'] ?? ''));
                                $discountPercent = $plan['discount_percent'] ?? null;
                                if ($discountLabel === '' && is_numeric($discountPercent) && (float) $discountPercent > 0) {
                                    $discountLabel = 'Save '.rtrim(rtrim(number_format((float) $discountPercent, 2), '0'), '.').'%';
                                }
                            @endphp

                            <div class="sub-plan-card {{ $popular ? 'popular' : '' }}"
                                 wire:click="selectPlan('{{ $plan['key'] }}')"
                                 role="button" tabindex="0"
                                 onkeydown="if(event.key==='Enter'||event.key===' ')this.click()">

                                @if($popular)
                                    <div class="sub-popular-badge">Most Popular</div>
                                @endif
                                @if($discountLabel !== '')
                                    <div class="sub-discount-badge">{{ $discountLabel }}</div>
                                @endif

                                <div class="sub-plan-icon {{ $iconClass }}">{{ $icons[$i % count($icons)] }}</div>
                                <div class="sub-plan-name">{{ $plan['name'] ?? 'Plan' }}</div>
                                <div class="sub-plan-cycle">{{ $plan['billing_cycle'] ?? 'month' }}</div>

                                <div class="sub-plan-price">
                                    <span class="sub-plan-price-currency">{{ $settings['currency'] ?? 'USD' }}</span>
                                    <span class="sub-plan-price-amount">{{ number_format((float)($plan['price'] ?? 0), 0) }}</span>
                                    <span class="sub-plan-price-per">/ {{ $plan['billing_cycle'] ?? 'mo' }}</span>
                                </div>

                                <hr class="sub-card-divider">

                                <ul class="sub-feature-list">
                                    <li class="sub-feature-item yes">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                        <span><strong>{{ ($plan['limits']['users'] ?? null) ? $plan['limits']['users'].' Users' : 'Unlimited Users' }}</strong></span>
                                    </li>
                                    <li class="sub-feature-item yes">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                        <span><strong>{{ ($plan['limits']['products'] ?? null) ? $plan['limits']['products'].' Products' : 'Unlimited Products' }}</strong></span>
                                    </li>
                                    <li class="sub-feature-item {{ ($plan['features']['ai_email'] ?? false) ? 'yes' : 'no' }}">
                                        @if($plan['features']['ai_email'] ?? false)
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                        @endif
                                        <span>AI Email Composer</span>
                                    </li>
                                    <li class="sub-feature-item {{ ($plan['features']['inventory'] ?? false) ? 'yes' : 'no' }}">
                                        @if($plan['features']['inventory'] ?? false)
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                        @endif
                                        <span>Inventory Management</span>
                                    </li>
                                    <li class="sub-feature-item {{ ($plan['features']['analytics'] ?? false) ? 'yes' : 'no' }}">
                                        @if($plan['features']['analytics'] ?? false)
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                        @endif
                                        <span>Advanced Analytics</span>
                                    </li>
                                </ul>

                                <button type="button" class="sub-select-btn" wire:click.stop="selectPlan('{{ $plan['key'] }}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                    Get Started
                                </button>
                            </div>
                        @endforeach
                    </div>

                    {{-- Trust strip --}}
                    <div class="sub-trust-strip">
                        <span class="sub-trust-label">We accept</span>
                        <span class="sub-trust-badge">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                            Stripe
                        </span>
                        <span class="sub-trust-badge">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                            Razorpay
                        </span>
                        <span class="sub-trust-badge">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                            PhonePe
                        </span>
                        <span class="sub-trust-badge" style="margin-left:auto">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
                            256-bit SSL Encrypted
                        </span>
                    </div>

                {{-- ════════════════════ STEP 2 – CHECKOUT ════════════════════ --}}
                @else
                    @php
                        $selectedPlan = collect($plans)->first(fn (array $p) => ($p['key'] ?? '') === $selectedPlanKey);
                    @endphp

                    {{-- Selected plan bar --}}
                    <div class="sub-selected-plan-bar">
                        <div class="sub-selected-plan-info">
                            <div class="sub-selected-plan-label">Selected Plan</div>
                            <div class="sub-selected-plan-name">{{ $selectedPlan['name'] ?? 'Plan' }}</div>
                            <div class="sub-selected-plan-price">
                                {{ $settings['currency'] ?? 'USD' }} {{ number_format((float)($selectedPlan['price'] ?? 0), 2) }}
                                / {{ $selectedPlan['billing_cycle'] ?? 'month' }}
                            </div>
                        </div>
                        <button type="button" class="sub-change-btn" wire:click="backToPlans">
                            ← Change Plan
                        </button>
                    </div>

                    <div class="sub-checkout-grid">

                        {{-- ── LEFT: Billing form + payment method + currency ─── --}}
                        <div class="sub-form-area">
                            <div class="sub-section-label">Billing Information</div>
                            {{ $this->form }}

                            {{-- ── Payment Method ───────────────────────────────── --}}
                            <div style="margin-top:1.5rem">
                                <div class="sub-section-label">Payment Method</div>
                                <div class="sub-pm-grid">

                                    {{-- Stripe --}}
                                    <label class="sub-pm-option {{ $paymentMethod === 'stripe' ? 'selected' : '' }}">
                                        <input type="radio" wire:model.live="paymentMethod" value="stripe">
                                        <div class="sub-pm-check">
                                            <svg viewBox="0 0 24 24" fill="none" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                                        </div>
                                        <div class="sub-pm-logo">💳</div>
                                        <div class="sub-pm-name">Stripe</div>
                                        <div class="sub-pm-note">All currencies</div>
                                    </label>

                                    {{-- Razorpay --}}
                                    <label class="sub-pm-option {{ $paymentMethod === 'razorpay' ? 'selected' : '' }}">
                                        <input type="radio" wire:model.live="paymentMethod" value="razorpay">
                                        <div class="sub-pm-check">
                                            <svg viewBox="0 0 24 24" fill="none" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                                        </div>
                                        <div class="sub-pm-logo">🇮🇳</div>
                                        <div class="sub-pm-name">Razorpay</div>
                                        <div class="sub-pm-note">INR only</div>
                                    </label>

                                    {{-- PhonePe --}}
                                    <label class="sub-pm-option {{ $paymentMethod === 'phonepe' ? 'selected' : '' }}">
                                        <input type="radio" wire:model.live="paymentMethod" value="phonepe">
                                        <div class="sub-pm-check">
                                            <svg viewBox="0 0 24 24" fill="none" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                                        </div>
                                        <div class="sub-pm-logo">📱</div>
                                        <div class="sub-pm-name">PhonePe</div>
                                        <div class="sub-pm-note">INR only</div>
                                    </label>

                                </div>
                            </div>

                            {{-- ── Currency ─────────────────────────────────────── --}}
                            <div>
                                <div class="sub-section-label">Currency</div>

                                @if(in_array($paymentMethod, ['razorpay', 'phonepe']))
                                    {{-- Locked to INR --}}
                                    <div class="sub-inr-notice">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/>
                                        </svg>
                                        Amounts shown below are converted from USD at ₹{{ number_format($usdToInrRate, 2) }}
                                    </div>
                                    <div class="sub-currency-row">
                                        <span class="sub-currency-chip selected locked">₹ INR</span>
                                    </div>
                                @else
                                    {{-- Stripe: all currencies selectable --}}
                                    @php $symbols = ['USD'=>'$','INR'=>'₹','EUR'=>'€','GBP'=>'£','AED'=>'د.إ','SGD'=>'S$']; @endphp
                                    <div class="sub-currency-row">
                                        @foreach($currencyOptions as $code => $label)
                                            <label class="sub-currency-chip {{ $selectedCurrency === $code ? 'selected' : '' }}">
                                                <input type="radio" wire:model.live="selectedCurrency" value="{{ $code }}">
                                                {{ ($symbols[$code] ?? '') }} {{ $code }}
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- ── RIGHT: Order Summary ──────────────────────────── --}}
                        <div class="sub-summary-card">
                            <div class="sub-summary-title">Order Summary</div>

                            @php $t = $this->calculateTotals(); @endphp

                            <div class="sub-breakdown-table">
                                <div class="sub-breakdown-row">
                                    <span class="sub-breakdown-label">Plan</span>
                                    <span class="sub-breakdown-value">{{ $t['plan']['name'] ?? '—' }}</span>
                                </div>
                                <div class="sub-breakdown-row">
                                    <span class="sub-breakdown-label">Base Price</span>
                                    <span class="sub-breakdown-value">{{ number_format($t['price'], 2) }} {{ $t['currency'] }}</span>
                                </div>
                                <div class="sub-breakdown-row">
                                    <span class="sub-breakdown-label">GST / Tax</span>
                                    <span class="sub-breakdown-value">{{ number_format($t['tax'], 2) }} {{ $t['currency'] }}</span>
                                </div>
                                <div class="sub-breakdown-row">
                                    <span class="sub-breakdown-label">Platform Fee</span>
                                    <span class="sub-breakdown-value">{{ number_format($t['platform_fee'], 2) }} {{ $t['currency'] }}</span>
                                </div>
                                <div class="sub-breakdown-total">
                                    <span class="sub-breakdown-label">Total Due</span>
                                    <span class="sub-breakdown-value">{{ number_format($t['total'], 2) }} {{ $t['currency'] }}</span>
                                </div>
                            </div>

                            @if($t['currency'] !== 'USD' && $t['fx_rate'] != 1.0)
                                <div class="sub-fx-hint">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                                    </svg>
                                    Rate: 1 USD = {{ number_format($t['fx_rate'], 2) }} {{ $t['currency'] }}
                                </div>
                            @endif

                            <button type="button" class="sub-pay-btn" wire:click="processPayment">
                                Pay &amp; Activate
                            </button>

                            <div class="sub-secure-row">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="5" y="11" width="14" height="10" rx="2"/>
                                    <path d="M8 11V7a4 4 0 018 0v4"/>
                                </svg>
                                Payments are secure &amp; encrypted. Cancel anytime.
                            </div>
                        </div>

                    </div>{{-- /.sub-checkout-grid --}}
                @endif

            </div>{{-- /.sub-body --}}

        </x-filament::modal>
    @endif
</div>

@once
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (() => {
        const componentId = @json($this->getId());
        const getComponent = () => window.Livewire?.find ? window.Livewire.find(componentId) : null;

        const openRazorpay = (detail) => {
            const payload = detail?.payload || detail || {};
            if (!payload?.key) return;
            if (!window.Razorpay) { console.error('Razorpay checkout script not loaded.'); return; }

            const component = getComponent();
            const options = {
                ...payload,
                handler: (response) => {
                    component?.call(
                        'completeRazorpayPayment',
                        response?.razorpay_payment_id || '',
                        response?.razorpay_order_id  || '',
                        response?.razorpay_signature  || ''
                    );
                },
                modal: { ondismiss: () => component?.call('handleRazorpayCancelled') },
            };

            const instance = new window.Razorpay(options);
            instance.on('payment.failed', () => component?.call('handleRazorpayCancelled'));
            instance.open();
        };

        const bind = () => {
            window.addEventListener('razorpay-checkout',   (e) => openRazorpay(e.detail));
            document.addEventListener('razorpay-checkout', (e) => openRazorpay(e.detail));
        };

        document.readyState === 'loading'
            ? document.addEventListener('DOMContentLoaded', bind)
            : bind();
    })();
</script>
@endonce