<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TenantDatabaseManager;
use App\Services\TenantResolver;
use App\Services\SubscriptionService;
use App\Support\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionRazorpayController extends Controller
{
    public function checkout(Request $request): View|RedirectResponse
    {
        $this->activateTenantFromRequest($request);
        $user = Auth::guard('tenant')->user();
        if (! $user instanceof User || $user->role !== UserRole::ORG_ADMIN) {
            return redirect()->to('/' . (string) $request->route('tenant') . '/login');
        }

        $payload = session()->get($this->sessionKey($user));
        if (! is_array($payload) || empty($payload['order_id'])) {
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('error', 'Payment session expired. Please try again.');
        }

        [$key, $secret] = $this->razorpayKeys();
        if ($key === '') {
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('error', 'Razorpay is not configured.');
        }

        return view('filament.pages.razorpay-checkout', [
            'razorpayKey' => $key,
            'orderId' => (string) $payload['order_id'],
            'amount' => (int) ($payload['amount'] ?? 0),
            'currency' => (string) ($payload['currency'] ?? 'INR'),
            'prefill' => (array) ($payload['prefill'] ?? []),
            'notes' => (array) ($payload['notes'] ?? []),
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $this->activateTenantFromRequest($request);
        $user = Auth::guard('tenant')->user();
        if (! $user instanceof User || $user->role !== UserRole::ORG_ADMIN) {
            return redirect()->to('/' . (string) $request->route('tenant') . '/login');
        }

        $request->validate([
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        [, $secret] = $this->razorpayKeys();
        if ($secret === '') {
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('error', 'Razorpay is not configured.');
        }

        $paymentId = (string) $request->input('razorpay_payment_id');
        $orderId = (string) $request->input('razorpay_order_id');
        $signature = (string) $request->input('razorpay_signature');

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $secret);
        if (! hash_equals($expected, $signature)) {
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('error', 'Payment verification failed.');
        }

        $payload = session()->get($this->sessionKey($user));
        if (! is_array($payload) || empty($payload['plan_key']) || empty($payload['billing_details'])) {
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('error', 'Payment session expired. Please try again.');
        }

        $currency = strtoupper((string) ($payload['currency'] ?? 'INR'));
        app(SubscriptionService::class)->activate(
            $user,
            (string) $payload['plan_key'],
            (array) $payload['billing_details'],
            'razorpay',
            $paymentId,
            [
                'razorpay_order_id' => $orderId,
                'razorpay_signature' => $signature,
            ],
            $currency,
        );

        session()->forget($this->sessionKey($user));

        return redirect()
            ->route('filament.admin.pages.subscription')
            ->with('success', 'Payment successful and subscription activated.');
    }

    private function sessionKey(User $user): string
    {
        return 'razorpay_checkout_' . $user->id;
    }

    private function activateTenantFromRequest(Request $request): void
    {
        $tenant = app(TenantResolver::class)->resolveFromRequest($request);
        if (! $tenant) {
            abort(404, 'Tenant not found.');
        }

        if (blank($tenant->database)) {
            $prefix = (string) config('tenancy.database_prefix', 'tenant_');
            $slug = (string) ($tenant->slug ?: $tenant->id);
            $tenant->forceFill([
                'database' => strtolower($prefix . Str::of($slug)->slug('_')->value()),
            ])->save();
        }

        app(TenantDatabaseManager::class)->activateTenantConnection($tenant);
        $request->session()->put('tenant_id', $tenant->id);
        $request->session()->put('tenant_slug', $tenant->slug ?: $tenant->id);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function razorpayKeys(): array
    {
        $key = (string) config('payments.razorpay.key');
        $secret = (string) config('payments.razorpay.secret');

        if ($key === '' || $secret === '') {
            $key = (string) env('RAZORPAY_KEY_ID', env('RAZORPAY_TEST_KEY', ''));
            $secret = (string) env('RAZORPAY_KEY_SECRET', env('RAZORPAY_TEST_SECRET', ''));
        }

        return [$key, $secret];
    }
}
