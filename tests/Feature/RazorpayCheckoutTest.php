<?php

namespace Tests\Feature;

use App\Livewire\SubscriptionOnboarding;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class RazorpayCheckoutTest extends TestCase
{
    public function test_razorpay_popup_event_is_sent_and_payload_stored()
    {
        // mock order creation to avoid external API call
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_123',
            ], 200),
        ]);

        // create tenant context (no factory exists for Tenant)
        $tenant = Tenant::create([
            'slug' => 'razorpay-tenant',
            // the in-memory landlord schema only contains slug/database
        ]);

        // provide a landlord organization and an org-admin user.  The
        // Organization model fires an event that attempts to create a tenant
        // record, which fails against our pared-down test schema, so temporarily
        // suppress events here.
        $org = Organization::withoutEvents(function () {
            return Organization::create([
                'name' => 'Test Org',
                'slug' => 'test-org',
            ]);
        });

        // create org admin user record on the landlord connection (the
        // default user factory would have tried to create a table that doesn't
        // exist in this pared‑down environment).
        $password = bcrypt('password');
        $userId = \DB::connection('landlord')
            ->table('users')
            ->insertGetId([
                'role' => UserRole::ORG_ADMIN,
                'organization_id' => $org->id,
                'email' => 'orgadmin@example.com',
                'password' => $password,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        /** @var User */
        $orgAdmin = User::on('landlord')->find($userId);

        // simulate tenant session values that middleware checks
        $this->withSession([
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
        ]);

        $this->actingAs($orgAdmin, 'tenant');

        Livewire::test(SubscriptionOnboarding::class)
            ->set('selectedPlanKey', 'basic')
            ->set('data.first_name', 'John')
            ->set('data.last_name', 'Doe')
            ->set('data.org_email', 'org@example.com')
            ->set('data.contact_email', 'user@example.com')
            ->set('data.mobile_number', '1234567890')
            // start with USD to ensure conversion takes place
            ->set('data.currency', 'USD')
            ->set('data.payment_method', 'stripe')
            // switching to razorpay should lock currency to INR via form logic
            ->set('data.payment_method', 'razorpay')
            ->assertSet('data.currency', 'INR')
            ->call('processPayment')
            ->assertDispatchedBrowserEvent('razorpay-checkout', function ($payload) use (&$captured) {
                $captured = $payload;
                // we expect payload to be INR and amount to be positive
                $this->assertEquals('INR', $payload['currency']);
                $this->assertGreaterThan(0, $payload['amount']);
                return true;
            });

        $sessionKey = 'razorpay_checkout_' . $orgAdmin->id;
        $this->assertNotNull(session($sessionKey));
        $this->assertEquals('order_123', session($sessionKey)['order_id'] ?? '');
        $this->assertEquals($captured['amount'], session($sessionKey)['amount'] ?? 0);
        $this->assertEquals('INR', session($sessionKey)['currency'] ?? '');
    }
}
