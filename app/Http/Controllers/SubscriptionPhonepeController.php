<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TenantDatabaseManager;
use App\Services\TenantResolver;
use App\Services\SubscriptionService;
use App\Support\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SubscriptionPhonepeController extends Controller
{
    public function checkout(Request $request): RedirectResponse|View
    {
        $this->activateTenantFromRequest($request);
        $user = Auth::guard('tenant')->user();
        if (! $user instanceof User || $user->role !== UserRole::ORG_ADMIN) {
            return redirect()->to('/' . (string) $request->route('tenant') . '/login');
        }

        $payload = session()->get($this->sessionKey($user));
        if (! is_array($payload) || empty($payload['merchant_transaction_id'])) {
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('error', 'Payment session expired. Please try again.');
        }

        $merchantId = (string) config('payments.phonepe.merchant_id');
        $saltKey = (string) config('payments.phonepe.salt_key');
        $saltIndex = (string) (config('payments.phonepe.salt_index') ?? '1');
        $env = (string) (config('payments.phonepe.env') ?? 'sandbox');

        if ($merchantId === '' || $saltKey === '') {
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('error', 'PhonePe is not configured.');
        }

        $tenantSlug = (string) ($request->route('tenant') ?? $payload['tenant'] ?? '');
        $redirectUrl = route('tenant.subscription.phonepe.callback', ['tenant' => $tenantSlug]);

        $currency = strtoupper((string) ($payload['currency'] ?? 'INR'));
        $requestData = [
            'merchantId' => $merchantId,
            'merchantTransactionId' => (string) $payload['merchant_transaction_id'],
            'merchantUserId' => (string) ($payload['merchant_user_id'] ?? $user->id),
            'amount' => (int) ($payload['amount'] ?? 0),
            'currency' => $currency,
            'redirectUrl' => $redirectUrl,
            'callbackUrl' => $redirectUrl,
            'redirectMode' => 'POST',
            'mobileNumber' => (string) ($payload['prefill']['contact'] ?? ''),
            'paymentInstrument' => [
                'type' => 'PAY_PAGE',
            ],
        ];

        $encoded = base64_encode(json_encode($requestData));
        $path = '/pg/v1/pay';
        $xVerify = hash('sha256', $encoded . $path . $saltKey) . '###' . $saltIndex;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-VERIFY' => $xVerify,
            'X-MERCHANT-ID' => $merchantId,
        ])->acceptJson()
            ->timeout(20)
            ->post($this->phonepeBaseUrl($env) . '/pay', [
                'request' => $encoded,
            ]);

        if (! $response->successful() || ! ($response->json('success') ?? false)) {
            $error = (string) ($response->json('cause') ?? $response->body());
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('error', $error !== '' ? $error : 'Unable to initiate PhonePe payment.');
        }

        $redirectTo = (string) data_get($response->json(), 'data.instrumentResponse.redirectInfo.url');
        if ($redirectTo === '') {
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('error', 'PhonePe did not return a redirect URL.');
        }

        return view('filament.pages.phonepe-checkout', [
            'redirect' => $redirectTo,
            'amount' => (int) ($payload['amount'] ?? 0),
            'currency' => $currency,
            'merchantTransactionId' => (string) $payload['merchant_transaction_id'],
            'tenant' => $tenantSlug,
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $this->activateTenantFromRequest($request);
        $data = $request->all();
        $merchantTransactionId = (string) ($data['merchantTransactionId'] ?? $data['transactionId'] ?? '');
        $providerReferenceId = $data['transactionId'] ?? null;

        $user = Auth::guard('tenant')->user();
        $payload = null;

        if ($user instanceof User) {
            $payload = session()->get($this->sessionKey($user));
        }

        if (! is_array($payload) && $merchantTransactionId !== '') {
            $cached = Cache::get($this->cacheKey($merchantTransactionId));
            if (is_array($cached) && ! empty($cached['user_id'])) {
                $cachedUser = User::query()->find((int) $cached['user_id']);
                if ($cachedUser instanceof User && $cachedUser->role === UserRole::ORG_ADMIN) {
                    Auth::guard('tenant')->login($cachedUser);
                    $user = $cachedUser;
                    $payload = $cached;
                }
            }
        }

        if (! $user instanceof User || $user->role !== UserRole::ORG_ADMIN) {
            return redirect()->to('/' . (string) $request->route('tenant') . '/login');
        }

        if (! is_array($payload)) {
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('error', 'Payment session expired. Please try again.');
        }

        $status = $this->normalizeStatus((string) ($data['code'] ?? ''), (string) ($data['status'] ?? ''));
        $rawStatus = null;

        if ($merchantTransactionId !== '' && $status === 'pending') {
            $statusResponse = $this->fetchPhonePeStatus($merchantTransactionId);
            $status = $statusResponse['status'];
            $providerReferenceId = $statusResponse['provider_reference_id'] ?? $providerReferenceId;
            $rawStatus = $statusResponse['raw'];
        }

        if ($status === 'pending') {
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('warning', 'PhonePe payment is pending. Please refresh after a moment.');
        }

        $referenceId = $providerReferenceId ?? $merchantTransactionId;

        if ($status === 'success') {
            $currency = strtoupper((string) ($payload['currency'] ?? 'INR'));
            app(SubscriptionService::class)->activate(
                $user,
                (string) ($payload['plan_key'] ?? ''),
                (array) ($payload['billing_details'] ?? []),
                'phonepe',
                $referenceId,
                [
                    'phonepe_payload' => $data,
                    'phonepe_status_api' => $rawStatus,
                ],
                $currency,
            );
            session()->forget($this->sessionKey($user));
            if ($merchantTransactionId !== '') {
                Cache::forget($this->cacheKey($merchantTransactionId));
            }
            return redirect()
                ->route('filament.admin.pages.subscription')
                ->with('success', 'Payment successful and subscription activated.');
        }

        session()->forget($this->sessionKey($user));
        if ($merchantTransactionId !== '') {
            Cache::forget($this->cacheKey($merchantTransactionId));
        }

        return redirect()
            ->route('filament.admin.pages.subscription')
            ->with('error', 'PhonePe payment failed or was cancelled.');
    }

    private function sessionKey(User $user): string
    {
        return 'phonepe_checkout_' . $user->id;
    }

    private function cacheKey(string $merchantTransactionId): string
    {
        return 'phonepe_checkout:' . $merchantTransactionId;
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

    private function normalizeStatus(string $code, string $status): string
    {
        $code = strtoupper(trim($code));
        $status = strtoupper(trim($status));

        if (in_array($code, ['PAYMENT_SUCCESS', 'SUCCESS'], true) || in_array($status, ['SUCCESS', 'COMPLETED'], true)) {
            return 'success';
        }

        if (in_array($code, ['PAYMENT_ERROR', 'PAYMENT_FAILED', 'PAYMENT_DECLINED'], true) || in_array($status, ['FAILED', 'DECLINED', 'ERROR'], true)) {
            return 'failed';
        }

        return 'pending';
    }

    private function fetchPhonePeStatus(string $merchantTransactionId): array
    {
        $merchantId = (string) config('payments.phonepe.merchant_id');
        $saltKey = (string) config('payments.phonepe.salt_key');
        $saltIndex = (string) (config('payments.phonepe.salt_index') ?? '1');
        $env = (string) (config('payments.phonepe.env') ?? 'sandbox');

        $path = "/pg/v1/status/{$merchantId}/{$merchantTransactionId}";
        $xVerify = hash('sha256', $path . $saltKey) . '###' . $saltIndex;
        $url = $this->phonepeBaseUrl($env) . '/status/' . $merchantId . '/' . $merchantTransactionId;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-VERIFY' => $xVerify,
            'X-MERCHANT-ID' => $merchantId,
        ])->timeout(20)
            ->get($url);

        if (! $response->successful()) {
            return [
                'status' => 'pending',
                'provider_reference_id' => null,
                'raw' => ['error' => $response->body()],
            ];
        }

        $decoded = $response->json();
        $state = strtoupper((string) ($decoded['data']['state'] ?? ''));
        $providerReferenceId = $decoded['data']['transactionId'] ?? null;

        if ($state === 'COMPLETED') {
            return [
                'status' => 'success',
                'provider_reference_id' => $providerReferenceId,
                'raw' => $decoded,
            ];
        }

        if (in_array($state, ['FAILED', 'DECLINED', 'EXPIRED'], true)) {
            return [
                'status' => 'failed',
                'provider_reference_id' => $providerReferenceId,
                'raw' => $decoded,
            ];
        }

        return [
            'status' => 'pending',
            'provider_reference_id' => $providerReferenceId,
            'raw' => $decoded,
        ];
    }

    private function phonepeBaseUrl(string $env): string
    {
        if (strtolower($env) === 'production') {
            return 'https://api.phonepe.com/apis/pg/v1';
        }

        return 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1';
    }

}
