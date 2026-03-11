<?php

namespace App\Livewire;

use App\Models\Organization;
use App\Models\User;
use App\Services\PlanCatalogService;
use App\Services\SubscriptionService;
use App\Services\TenantDatabaseManager;
use App\Support\OrganizationEmailFormatter;
use App\Support\TenantUserMirror;
use App\Support\UserRole;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Component;

class SubscriptionOnboarding extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public string $step = 'plans';
    public ?string $selectedPlanKey = null;
    public array $plans = [];
    public array $settings = [];
    public bool $shouldShow = false;
    public bool $canChangeOrgEmail = true;
    public string $originalOrgEmail = '';
    public array $currencyOptions = [
        'USD' => 'USD',
        'INR' => 'INR',
        'EUR' => 'EUR',
        'GBP' => 'GBP',
        'AED' => 'AED',
        'SGD' => 'SGD',
    ];

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user instanceof User || $user->role !== UserRole::ORG_ADMIN) {
            return;
        }

        $organization = $user->organization;
        if (! $organization) {
            return;
        }

        $active = app(SubscriptionService::class)->getActiveSubscription($organization);
        if ($active) {
            return;
        }

        $this->plans = app(PlanCatalogService::class)->visible();
        $this->settings = app(PlanCatalogService::class)->settings();
        $this->shouldShow = true;

        $this->originalOrgEmail = (string) $organization->email;
        $this->canChangeOrgEmail = ! (bool) data_get($organization->settings, 'org_email_change_used', false);

        $this->form->fill([
            'first_name' => (string) ($user->first_name ?? ''),
            'last_name' => (string) ($user->last_name ?? ''),
            'org_email' => (string) $organization->email,
            'contact_email' => (string) ($user->contact_email ?: ''),
            'mobile_number' => (string) ($organization->phone ?? ''),
            'payment_method' => 'stripe',
            'currency' => (string) ($this->settings['currency'] ?? 'USD'),
        ]);

        $this->dispatch('open-modal', id: $this->getModalId());
    }

    public function render()
    {
        return view('livewire.subscription-onboarding');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->maxLength(120),
                TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->maxLength(120),
                Placeholder::make('received_org_email')
                    ->label('Organization Email (Received)')
                    ->content(fn () => $this->originalOrgEmail ?: '-'),
                TextInput::make('org_email')
                    ->label('Organization Email (Login)')
                    ->email()
                    ->required()
                    ->disabled(fn () => ! $this->canChangeOrgEmail)
                    ->helperText($this->canChangeOrgEmail
                        ? 'You can change the organization email once.'
                        : 'Organization email change already used.'),
                Placeholder::make('downstream_pattern')
                    ->label('Auto-generated user emails')
                    ->content(function (callable $get): string {
                        $orgEmail = (string) ($get('org_email') ?: $this->originalOrgEmail);
                        $domain = OrganizationEmailFormatter::normalizeDomain($orgEmail);
                        return "user_firstname.role@{$domain}";
                    }),
                TextInput::make('contact_email')
                    ->label('Actual Gmail (Contact)')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('mobile_number')
                    ->label('Mobile Number')
                    ->tel()
                    ->maxLength(30),
                Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'stripe' => 'Stripe',
                        'razorpay' => 'Razorpay',
                        'phonepe' => 'PhonePe',
                    ])
                    ->required(),
                Select::make('currency')
                    ->label('Currency')
                    ->options(fn () => $this->currencyOptions)
                    ->required()
                    ->default(fn () => $this->settings['currency'] ?? 'USD')
                    ->disabled(function (callable $get): bool {
                        $method = strtolower(trim((string) $get('payment_method')));
                        return in_array($method, ['razorpay', 'phonepe'], true);
                    })
                    ->dehydrateStateUsing(function ($state, callable $get) {
                        $method = strtolower(trim((string) $get('payment_method')));
                        return in_array($method, ['razorpay', 'phonepe'], true) ? 'INR' : $state;
                    })
                    ->afterStateUpdated(function (callable $set, $state, callable $get): void {
                        $method = strtolower(trim((string) $get('payment_method')));
                        if (in_array($method, ['razorpay', 'phonepe'], true)) {
                            $set('currency', 'INR');
                        }
                    }),
                Placeholder::make('payment_breakdown')
                    ->label('Payment Breakdown')
                    ->content(fn () => $this->formatBreakdown())
                    ->columnStart(2),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function getModalId(): string
    {
        return 'subscription-onboarding';
    }

    public function selectPlan(string $key): void
    {
        $this->selectedPlanKey = $key;
        $this->step = 'checkout';
    }

    public function backToPlans(): void
    {
        $this->step = 'plans';
    }

    public function processPayment(): void
    {
        $state = $this->form->getState();

        $this->validate([
            'data.first_name' => ['required', 'string', 'max:120'],
            'data.last_name' => ['required', 'string', 'max:120'],
            'data.org_email' => ['required', 'email'],
            'data.contact_email' => ['required', 'email'],
            'data.mobile_number' => ['nullable', 'string', 'max:30'],
            'data.payment_method' => ['required', 'string'],
            'data.currency' => ['required', 'string'],
        ]);

        if (! $this->selectedPlanKey) {
            Notification::make()
                ->danger()
                ->title('Select a plan to continue')
                ->send();
            return;
        }

        $user = auth()->user();
        if (! $user instanceof User || $user->role !== UserRole::ORG_ADMIN) {
            return;
        }

        $organization = $user->organization;
        if (! $organization) {
            return;
        }

        if (! $this->applyOrgEmailChange($organization, (string) ($state['org_email'] ?? ''))) {
            return;
        }

        $organization->forceFill([
            'phone' => (string) ($state['mobile_number'] ?? $organization->phone),
        ])->save();

        if ($organization->tenant) {
            app(TenantDatabaseManager::class)->activateTenantConnection($organization->tenant);
            $tenantConnection = config('tenancy.tenant_connection', 'tenant');
            \Illuminate\Support\Facades\DB::connection($tenantConnection)
                ->table('organizations')
                ->where('id', $organization->id)
                ->update([
                    'phone' => (string) ($state['mobile_number'] ?? $organization->phone),
                    'updated_at' => now(),
                ]);
            app(TenantDatabaseManager::class)->activateLandlordConnection();
        }

        $user->forceFill([
            'first_name' => (string) ($state['first_name'] ?? $user->first_name),
            'last_name' => (string) ($state['last_name'] ?? $user->last_name),
            'contact_email' => (string) ($state['contact_email'] ?? $user->contact_email),
            'name' => trim((string) ($state['first_name'] ?? '').' '.(string) ($state['last_name'] ?? '')),
        ])->save();
        TenantUserMirror::syncToLandlord($user);

        $billingDetails = [
            'first_name' => (string) ($state['first_name'] ?? ''),
            'last_name' => (string) ($state['last_name'] ?? ''),
            'organization_email' => (string) ($state['org_email'] ?? ''),
            'contact_email' => (string) ($state['contact_email'] ?? ''),
            'mobile_number' => (string) ($state['mobile_number'] ?? ''),
            'received_org_email' => $this->originalOrgEmail,
        ];
        $paymentMethod = strtolower(trim((string) ($state['payment_method'] ?? 'stripe')));
        if (in_array($paymentMethod, ['razorpay', 'razor pay', 'razorepay', 'razor_pay'], true)) {
            $paymentMethod = 'razorpay';
        }
        $currency = strtoupper(trim((string) ($state['currency'] ?? ($this->settings['currency'] ?? 'USD'))));
        if (in_array($paymentMethod, ['razorpay', 'phonepe'], true)) {
            $currency = 'INR';
        }

        if ($paymentMethod === 'razorpay') {
            $checkout = $this->startRazorpayCheckout($user, $organization, $billingDetails);
            if (! $checkout) {
                return;
            }

            $this->redirectRoute('tenant.subscription.razorpay.checkout', [
                'tenant' => $organization->slug ?: $organization->tenant_id,
            ], navigate: true);
            return;
        }

        app(SubscriptionService::class)->activate(
            $user,
            $this->selectedPlanKey,
            $billingDetails,
            $paymentMethod,
            paymentReference: null,
            paymentMeta: [],
            currencyOverride: $currency,
        );

        $this->shouldShow = false;
        $this->dispatch('close-modal', id: $this->getModalId());

        Notification::make()
            ->success()
            ->title('Subscription activated')
            ->body('Your organization plan is now active.')
            ->send();
    }

    public function completeRazorpayPayment(string $paymentId, string $orderId, string $signature): void
    {
        $user = auth()->user();
        if (! $user instanceof User || $user->role !== UserRole::ORG_ADMIN) {
            return;
        }

        $checkout = $this->getRazorpayCheckout($user);
        if (! $checkout || ($checkout['order_id'] ?? '') !== $orderId) {
            Notification::make()
                ->danger()
                ->title('Payment session expired')
                ->send();
            return;
        }

        $secret = (string) config('payments.razorpay.secret');
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $secret);
        if (! hash_equals($expected, $signature)) {
            Notification::make()
                ->danger()
                ->title('Payment verification failed')
                ->send();
            return;
        }

        $billingDetails = (array) ($checkout['billing_details'] ?? []);
        $planKey = (string) ($checkout['plan_key'] ?? '');
        if ($planKey === '') {
            Notification::make()
                ->danger()
                ->title('Plan not found for this payment')
                ->send();
            return;
        }

        app(SubscriptionService::class)->activate(
            $user,
            $planKey,
            $billingDetails,
            'razorpay',
            $paymentId,
            [
                'razorpay_order_id' => $orderId,
                'razorpay_signature' => $signature,
            ],
            'INR',
        );

        $this->clearRazorpayCheckout($user);
        $this->shouldShow = false;
        $this->dispatch('close-modal', id: $this->getModalId());

        Notification::make()
            ->success()
            ->title('Subscription activated')
            ->body('Your organization plan is now active.')
            ->send();
    }

    public function handleRazorpayCancelled(): void
    {
        Notification::make()
            ->warning()
            ->title('Payment cancelled')
            ->body('You can try again when ready.')
            ->send();
    }

    private function applyOrgEmailChange(Organization $organization, string $newEmail): bool
    {
        $newEmail = strtolower(trim($newEmail));
        if ($newEmail === '' || $newEmail === strtolower(trim((string) $organization->email))) {
            return true;
        }

        if (! $this->canChangeOrgEmail) {
            return true;
        }

        $existing = User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$newEmail])
            ->where('organization_id', '!=', $organization->id)
            ->exists();
        if ($existing) {
            Notification::make()
                ->danger()
                ->title('Organization email already in use')
                ->send();
            return false;
        }

        $organization->forceFill(['email' => $newEmail])->save();

        User::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('role', UserRole::ORG_ADMIN)
            ->update(['email' => $newEmail]);

        $landlord = config('tenancy.landlord_connection', 'landlord');
        User::withoutGlobalScopes()
            ->on($landlord)
            ->where('organization_id', $organization->id)
            ->where('role', UserRole::ORG_ADMIN)
            ->update(['email' => $newEmail]);

        if ($organization->tenant) {
            app(TenantDatabaseManager::class)->activateTenantConnection($organization->tenant);
            $tenantConnection = config('tenancy.tenant_connection', 'tenant');
            \Illuminate\Support\Facades\DB::connection($tenantConnection)
                ->table('organizations')
                ->where('id', $organization->id)
                ->update([
                    'email' => $newEmail,
                    'updated_at' => now(),
                ]);
            \Illuminate\Support\Facades\DB::connection($tenantConnection)
                ->table('users')
                ->where('organization_id', $organization->id)
                ->where('role', UserRole::ORG_ADMIN)
                ->update([
                    'email' => $newEmail,
                    'updated_at' => now(),
                ]);
            app(TenantDatabaseManager::class)->activateLandlordConnection();

            $organization->tenant->forceFill([
                'data' => array_merge((array) ($organization->tenant->data ?? []), [
                    'login_email' => $newEmail,
                ]),
            ])->save();
        }

        $settings = (array) ($organization->settings ?? []);
        data_set($settings, 'org_email_change_used', true);
        $organization->forceFill(['settings' => $settings])->save();

        $this->originalOrgEmail = $newEmail;
        $this->canChangeOrgEmail = false;

        return true;
    }

    private function formatBreakdown(): HtmlString
    {
        $totals = $this->calculateTotals();
        $planName = (string) ($totals['plan']['name'] ?? 'Plan');
        $price = (float) ($totals['price'] ?? 0);
        $tax = (float) ($totals['tax'] ?? 0);
        $platform = (float) ($totals['platform_fee'] ?? 0);
        $total = (float) ($totals['total'] ?? 0);
        $currency = (string) ($totals['currency'] ?? 'USD');

        $rows = [
            'Plan' => $planName,
            'Price' => sprintf('%.2f %s', $price, $currency),
            'GST' => sprintf('%.2f %s', $tax, $currency),
            'Platform Fee' => sprintf('%.2f %s', $platform, $currency),
            'Total' => sprintf('%.2f %s', $total, $currency),
        ];

        $html = '<table style="width:100%; border-collapse:collapse;">';
        foreach ($rows as $label => $value) {
            $html .= '<tr>';
            $html .= '<td style="padding:4px 0; color:#6b7280; font-weight:600; text-align:left;">' . e($label) . '</td>';
            $html .= '<td style="padding:4px 0; color: #ffffff;; font-weight:600; text-align:right;">' . e($value) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        return new HtmlString($html);
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateTotals(): array
    {
        $plan = $this->selectedPlanKey
            ? collect($this->plans)->first(fn (array $p): bool => (string) ($p['key'] ?? '') === $this->selectedPlanKey)
            : null;

        $price = (float) ($plan['price'] ?? 0);
        $tax = round($price * (float) ($this->settings['tax_rate'] ?? 0), 2);
        $platform = (float) ($this->settings['platform_fee'] ?? 0);
        $total = round($price + $tax + $platform, 2);
        $currency = strtoupper(trim((string) data_get($this->data, 'currency', (string) ($this->settings['currency'] ?? 'USD'))));
        $method = strtolower(trim((string) data_get($this->data, 'payment_method', '')));
        if (in_array($method, ['razorpay', 'phonepe'], true)) {
            $currency = 'INR';
        }

        return [
            'plan' => $plan,
            'price' => $price,
            'tax' => $tax,
            'platform_fee' => $platform,
            'total' => $total,
            'currency' => $currency,
        ];
    }

    /**
     * @param array<string, mixed> $billingDetails
     * @return array<string, mixed>|null
     */
    private function startRazorpayCheckout(User $user, Organization $organization, array $billingDetails): ?array
    {
        [$key, $secret] = $this->razorpayKeys();
        if ($key === '' || $secret === '') {
            Notification::make()
                ->danger()
                ->title('Razorpay not configured')
                ->body('Set RAZORPAY_KEY_ID/RAZORPAY_KEY_SECRET or RAZORPAY_TEST_KEY/RAZORPAY_TEST_SECRET in .env and clear config cache.')
                ->send();
            return null;
        }

        $totals = $this->calculateTotals();
        $plan = (array) ($totals['plan'] ?? []);
        if (! $plan) {
            Notification::make()
                ->danger()
                ->title('Plan not found')
                ->send();
            return null;
        }

        $currency = 'INR';
        $amount = (int) round(((float) ($totals['total'] ?? 0)) * 100);
        if ($amount <= 0) {
            Notification::make()
                ->danger()
                ->title('Invalid payment amount')
                ->send();
            return null;
        }

        $receipt = 'sub_' . $organization->id . '_' . now()->format('YmdHis') . '_' . Str::random(6);

        $response = Http::withBasicAuth($key, $secret)
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post('https://api.razorpay.com/v1/orders', [
            'amount' => $amount,
            'currency' => $currency,
            'receipt' => $receipt,
            'payment_capture' => 1,
            'notes' => [
                'organization_id' => $organization->id,
                'plan_key' => $this->selectedPlanKey,
                'actor_id' => $user->id,
            ],
        ]);

        if (! $response->successful()) {
            $error = (string) ($response->json('error.description') ?? $response->json('error.reason') ?? $response->body());
            Notification::make()
                ->danger()
                ->title('Unable to start Razorpay payment')
                ->body($error !== '' ? $error : 'Please try again or contact support.')
                ->send();
            return null;
        }

        $orderId = (string) ($response->json('id') ?? '');
        if ($orderId === '') {
            Notification::make()
                ->danger()
                ->title('Razorpay order creation failed')
                ->send();
            return null;
        }

        $prefill = [
            'name' => trim(($billingDetails['first_name'] ?? '').' '.($billingDetails['last_name'] ?? '')),
            'email' => (string) ($billingDetails['contact_email'] ?? $billingDetails['organization_email'] ?? ''),
            'contact' => (string) ($billingDetails['mobile_number'] ?? ''),
        ];
        $notes = [
            'organization' => (string) $organization->name,
            'organization_id' => $organization->id,
        ];

        $this->storeRazorpayCheckout($user, [
            'order_id' => $orderId,
            'plan_key' => $this->selectedPlanKey,
            'billing_details' => $billingDetails,
            'amount' => $amount,
            'currency' => $currency,
            'organization_id' => $organization->id,
            'prefill' => $prefill,
            'notes' => $notes,
        ]);

        return [
            'key' => $key,
            'amount' => $amount,
            'currency' => $currency,
            'name' => config('app.name'),
            'description' => (string) ($plan['name'] ?? 'Subscription'),
            'order_id' => $orderId,
            'prefill' => $prefill,
            'notes' => $notes,
        ];
    }

    private function razorpaySessionKey(User $user): string
    {
        return 'razorpay_checkout_' . $user->id;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function storeRazorpayCheckout(User $user, array $payload): void
    {
        session()->put($this->razorpaySessionKey($user), $payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRazorpayCheckout(User $user): ?array
    {
        return session()->get($this->razorpaySessionKey($user));
    }

    private function clearRazorpayCheckout(User $user): void
    {
        session()->forget($this->razorpaySessionKey($user));
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
