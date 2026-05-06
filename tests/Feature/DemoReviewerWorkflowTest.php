<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Fine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DemoReviewerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_demo_accounts_and_lifecycle_examples_are_available(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', ['email' => 'admin@gantian.test', 'role' => User::ROLE_ADMIN]);
        $this->assertDatabaseHas('users', ['email' => 'staff@gantian.test', 'role' => User::ROLE_STAFF]);
        $this->assertDatabaseHas('users', ['email' => 'customer@gantian.test', 'role' => User::ROLE_CUSTOMER]);
        $this->assertDatabaseHas('users', ['email' => 'customer2@gantian.test', 'role' => User::ROLE_CUSTOMER]);

        foreach (Booking::STATUSES as $status) {
            $this->assertDatabaseHas('bookings', ['status' => $status]);
        }

        $this->assertDatabaseHas('fines', ['status' => Fine::STATUS_PAID]);
        $this->assertDatabaseHas('fines', ['status' => Fine::STATUS_UNPAID]);
    }

    public function test_reviewer_critical_routes_match_role_matrix(): void
    {
        $this->seed();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('customer.bookings'))->assertRedirect(route('login'));

        $customer = User::query()->where('email', 'customer@gantian.test')->firstOrFail();
        $staff = User::query()->where('email', 'staff@gantian.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@gantian.test')->firstOrFail();

        $this->actingAs($customer)->get(route('dashboard'))->assertOk();
        $this->actingAs($customer)->get(route('customer.catalog'))->assertOk();
        $this->actingAs($customer)->get(route('customer.bookings'))->assertOk();
        $this->actingAs($customer)->get(route('staff.bookings'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.items'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.reports'))->assertForbidden();

        $this->actingAs($staff)->get(route('dashboard'))->assertOk();
        $this->actingAs($staff)->get(route('staff.bookings'))->assertOk();
        $this->actingAs($staff)->get(route('customer.bookings'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.items'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.reports'))->assertForbidden();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('staff.bookings'))->assertOk();
        $this->actingAs($admin)->get(route('admin.items'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports'))->assertOk();
        $this->actingAs($admin)->get(route('customer.bookings'))->assertForbidden();
    }

    public function test_seeded_customer_booking_history_shows_only_customer_owned_demo_items(): void
    {
        $this->seed();

        $customer = User::query()->where('email', 'customer@gantian.test')->firstOrFail();

        $this->actingAs($customer)
            ->get(route('customer.bookings'))
            ->assertOk()
            ->assertSee('Sony Alpha A6400 Mirrorless Camera')
            ->assertSee('JBL PartyBox 310 Speaker')
            ->assertSee('DJI Mini 4 Pro Drone')
            ->assertDontSee('Epson EB-X500 Meeting Projector')
            ->assertDontSee('Manfrotto Compact Tripod');
    }

    public function test_demo_duplicate_booking_guard_remains_active(): void
    {
        $this->seed();

        $customer = User::query()->where('email', 'customer@gantian.test')->firstOrFail();
        $pendingBooking = Booking::query()
            ->where('user_id', $customer->id)
            ->where('status', Booking::STATUS_PENDING)
            ->firstOrFail();

        $this->actingAs($customer);

        Volt::test('pages.customer.catalog')
            ->set('selectedItemId', $pendingBooking->item_id)
            ->set('startDate', '2026-05-02')
            ->set('endDate', '2026-05-04')
            ->call('submitBooking')
            ->assertHasErrors(['startDate']);
    }
}
