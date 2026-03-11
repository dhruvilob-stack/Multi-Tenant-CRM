<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\UserRole;
use Tests\TestCase;

class TenantLoginTest extends TestCase
{

    public function test_manufacturer_can_login_and_land_on_correct_panel()
    {
        // prepare tenant and user
        $tenant = Tenant::create(['slug' => 'test-tenant']);

        // insert the manufacturer user directly into the tenant connection
        // since the default connection in tests is not aware of the tenancy
        // middleware.
        $password = bcrypt('password');
        $userId = \DB::connection(config('tenancy.tenant_connection', 'tenant'))
            ->table('users')
            ->insertGetId([
                'role' => UserRole::MANUFACTURER,
                'organization_id' => 1,
                'email' => 'manufacturer@example.com',
                'password' => $password,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $manufacturer = User::on(config('tenancy.tenant_connection', 'tenant'))->find($userId);

        // load the login page once – this is primarily to establish the
        // tenant session cookie. we intentionally bypass CSRF validation for
        // the subsequent post since the Filament-generated form relies on
        // javascript and a meta-tag which is awkward to parse here.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

        $response = $this->get("/{$tenant->slug}/login");
        $response->assertStatus(200);

        // submit credentials directly; the CSRF token is not required now.
        $login = $this->post("/{$tenant->slug}/login", [
            'email' => $manufacturer->email,
            'password' => 'password',
        ]);

        // should not loop, should redirect to manufacturer landing
        $login->assertRedirect("/{$tenant->slug}/manufacturer");

        // follow the redirect
        $follow = $this->get($login->headers->get('Location'));
        $follow->assertStatus(200);
        // expect content contains manufacturer panel string
        $this->assertStringContainsString('/manufacturer', $follow->baseResponse->getContent());
    }
}
