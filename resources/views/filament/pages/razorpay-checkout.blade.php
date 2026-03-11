<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment — {{ config('app.name') }}</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .card {
            background: #ffffff; border-radius: 1rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 8px 40px rgba(15,23,42,.10), 0 1px 4px rgba(15,23,42,.05);
            max-width: 420px; width: 100%; text-align: center;
        }
        .logo {
            width: 52px; height: 52px; border-radius: .75rem;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem; font-size: 1.5rem;
        }
        h1 { font-size: 1.2rem; font-weight: 700; color: #0f172a; margin-bottom: .4rem; }
        .sub { font-size: .875rem; color: #64748b; margin-bottom: 1.75rem; line-height: 1.5; }
        .amount-pill {
            display: inline-flex; align-items: baseline; gap: .35rem;
            background: #f0fdf4; border: 1px solid #bbf7d0;
            border-radius: 9999px; padding: .35rem 1.1rem; margin-bottom: 1.75rem;
        }
        .amount-pill .cur { font-size: .8rem; font-weight: 600; color: #166534; }
        .amount-pill .amt { font-size: 1.35rem; font-weight: 800; color: #15803d; }
        #pay-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            width: 100%; padding: .85rem 1.5rem;
            border-radius: .625rem; border: none; cursor: pointer;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff; font-size: .95rem; font-weight: 700;
            box-shadow: 0 4px 14px rgba(99,102,241,.35);
            transition: opacity .2s, transform .2s;
        }
        #pay-btn:hover { opacity: .92; transform: translateY(-1px); }
        #pay-btn:active { transform: translateY(0); }
        .countdown { font-size: .78rem; color: #94a3b8; margin-top: .9rem; }
        .countdown span { font-weight: 700; color: #6366f1; }
        .secure {
            display: flex; align-items: center; justify-content: center; gap: .4rem;
            font-size: .73rem; color: #94a3b8; margin-top: 1.25rem;
        }
        .secure svg { width: 12px; height: 12px; }
        .cancel-link {
            display: block; margin-top: .75rem;
            font-size: .78rem; color: #94a3b8; text-decoration: none;
        }
        .cancel-link:hover { color: #6366f1; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">💳</div>
        <h1>Complete your payment</h1>
        <p class="sub">
            Click the button below to open the secure Razorpay<br>
            payment window and complete your subscription.
        </p>

        @php $displayAmount = number_format($amount / 100, 2); @endphp

        <div class="amount-pill">
            <span class="cur">{{ $currency }}</span>
            <span class="amt">{{ $displayAmount }}</span>
        </div>

        <form id="rzp-form" method="POST"
              action="{{ route('tenant.subscription.razorpay.callback', ['tenant' => request()->route('tenant')]) }}">
            @csrf
            <input type="hidden" name="razorpay_payment_id" id="rzp_payment_id">
            <input type="hidden" name="razorpay_order_id"   id="rzp_order_id">
            <input type="hidden" name="razorpay_signature"  id="rzp_signature">
        </form>

        <button type="button" id="pay-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/>
            </svg>
            Pay {{ $currency }} {{ $displayAmount }}
        </button>

        <p class="countdown" id="countdown-msg">
            Opening automatically in <span id="countdown-sec">3</span>s&hellip;
        </p>

        <div class="secure">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/>
            </svg>
            256-bit SSL &middot; Powered by Razorpay
        </div>

        <a href="{{ route('filament.admin.pages.dashboard', ['tenant' => request()->route('tenant')]) }}"
           class="cancel-link">Cancel and go back</a>
    </div>

    <script>
    (() => {
        const cancelUrl = @json(route('filament.admin.pages.dashboard', ['tenant' => request()->route('tenant')]));

        const options = {
            key:         @json($razorpayKey),
            amount:      @json($amount),
            currency:    @json($currency),
            name:        @json(config('app.name')),
            description: 'Subscription Payment',
            order_id:    @json($orderId),
            prefill:     @json($prefill ?? []),
            notes:       @json($notes ?? []),
            theme:       { color: '#6366f1' },
            handler: function (response) {
                document.getElementById('rzp_payment_id').value = response.razorpay_payment_id || '';
                document.getElementById('rzp_order_id').value   = response.razorpay_order_id   || '';
                document.getElementById('rzp_signature').value  = response.razorpay_signature  || '';
                document.getElementById('rzp-form').submit();
            },
            modal: {
                ondismiss: function () {
                    window.location.href = cancelUrl;
                },
            },
        };

        let rzpInstance = null;

        const openCheckout = function () {
            if (!window.Razorpay) {
                alert('Razorpay could not be loaded. Please check your internet connection and try again.');
                return;
            }
            if (!rzpInstance) {
                rzpInstance = new window.Razorpay(options);
                rzpInstance.on('payment.failed', function () {
                    window.location.href = cancelUrl;
                });
            }
            rzpInstance.open();
        };

        const btn = document.getElementById('pay-btn');
        // Real user click — Razorpay never blocks this
        btn.addEventListener('click', openCheckout);

        // Countdown auto-clicks the BUTTON (not openCheckout directly) so
        // Razorpay still sees a user-gesture chain from the button's own
        // click event listener above
        let secs = 3;
        const secEl       = document.getElementById('countdown-sec');
        const countdownEl = document.getElementById('countdown-msg');

        const ticker = setInterval(function () {
            secs--;
            if (secEl) secEl.textContent = secs;
            if (secs <= 0) {
                clearInterval(ticker);
                if (countdownEl) countdownEl.textContent = 'Opening payment window\u2026';
                btn.click();
            }
        }, 1000);
    })();
    </script>
</body>
</html>