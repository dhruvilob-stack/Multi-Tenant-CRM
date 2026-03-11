<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_admin_can_impersonate_downward_role_and_is_redirected_with_alias()
    {
        // prepare a tenant and two users
        // Tenant model doesn't have a factory, create manually
        $tenant = Tenant::create([
            'slug' => 'test-tenant',
            // landlord schema in tests doesn't include name
        ]);

        /** @var User */
        $orgAdmin = User::factory()->create([
            'role' => UserRole::ORG_ADMIN,
            'organization_id' => 1,
        ]);

        /** @var User */
        $distributor = User::factory()->create([
            'role' => UserRole::DISTRIBUTOR,
            'organization_id' => $orgAdmin->organization_id,
        ]);

        // simulate tenant context in session
        $this->withSession([
            'tenant_id' => $tenant->id,
            'tenant_slug' => $tenant->slug,
        ]);

        // authenticate as org admin using tenant guard
        $this->actingAs($orgAdmin, 'tenant');

        $response = $this->get("/{$tenant->slug}/impersonate/{$distributor->id}");

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertStringContainsString('impersonated=1', $location);
        $this->assertStringContainsString('role_dashboard_alias=1', $location);
        $this->assertStringContainsString('role=distributor', $location);

        // follow the redirect once; should land on the panel root with query
        $follow = $this->get($location);
        $follow->assertStatus(200);
        $this->assertStringContainsString('/distributor', $follow->baseResponse->getContent());
    }
}
