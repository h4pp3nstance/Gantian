<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_middleware_allows_admin_to_act_as_staff(): void
    {
        Route::middleware(['web', 'role:staff'])
            ->get('/rbac/staff-only', fn () => response('ok'));

        $this->actingAs(User::factory()->admin()->create())
            ->get('/rbac/staff-only')
            ->assertOk();
    }

    public function test_role_middleware_does_not_allow_staff_to_act_as_admin(): void
    {
        Route::middleware(['web', 'role:admin'])
            ->get('/rbac/admin-only', fn () => response('ok'));

        $this->actingAs(User::factory()->staff()->create())
            ->get('/rbac/admin-only')
            ->assertForbidden();
    }

    public function test_role_middleware_accepts_multiple_actor_roles(): void
    {
        Route::middleware(['web', 'role:admin,customer'])
            ->get('/rbac/admin-or-customer', fn () => response('ok'));

        $this->actingAs(User::factory()->customer()->create())
            ->get('/rbac/admin-or-customer')
            ->assertOk();
    }

    public function test_customer_does_not_pass_staff_middleware(): void
    {
        Route::middleware(['web', 'role:staff'])
            ->get('/rbac/staff-capability', fn () => response('ok'));

        $this->actingAs(User::factory()->customer()->create())
            ->get('/rbac/staff-capability')
            ->assertForbidden();
    }

    public function test_gates_enforce_admin_staff_and_customer_boundaries(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();

        foreach (['manage-catalog', 'view-revenue-reports'] as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability));
            $this->assertFalse(Gate::forUser($staff)->allows($ability));
            $this->assertFalse(Gate::forUser($customer)->allows($ability));
        }

        foreach (['validate-bookings', 'process-checkout', 'process-checkin', 'issue-fines'] as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability));
            $this->assertTrue(Gate::forUser($staff)->allows($ability));
            $this->assertFalse(Gate::forUser($customer)->allows($ability));
        }

        $this->assertFalse(Gate::forUser($admin)->allows('submit-bookings'));
        $this->assertFalse(Gate::forUser($staff)->allows('submit-bookings'));
        $this->assertTrue(Gate::forUser($customer)->allows('submit-bookings'));
    }
}
