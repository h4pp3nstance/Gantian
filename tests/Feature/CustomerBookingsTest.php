<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Fine;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_only_their_own_booking_history(): void
    {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $ownedItem = Item::factory()->create(['name' => 'Owned Camera Kit']);
        $otherItem = Item::factory()->create(['name' => 'Other Customer Drone']);

        $booking = Booking::factory()
            ->approved()
            ->for($customer)
            ->for($ownedItem)
            ->create([
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
                'total_price' => '150000.00',
            ]);

        Fine::factory()
            ->unpaid()
            ->for($booking)
            ->create(['fine_amount' => '25000.00']);

        Booking::factory()
            ->completed()
            ->for($otherCustomer)
            ->for($otherItem)
            ->create(['total_price' => '990000.00']);

        $this->actingAs($customer)
            ->get(route('customer.bookings'))
            ->assertOk()
            ->assertSeeVolt('pages.customer.bookings')
            ->assertSee('My Bookings')
            ->assertSee('Track your rental requests and current booking status.')
            ->assertSee('Owned Camera Kit')
            ->assertSee('Approved')
            ->assertSee('Rp 150,000.00')
            ->assertSee('1 fine(s)')
            ->assertSee('Rp 25,000.00')
            ->assertDontSee('Other Customer Drone')
            ->assertDontSee('Rp 990,000.00');
    }

    public function test_customer_bookings_page_shows_all_lifecycle_statuses(): void
    {
        $customer = User::factory()->customer()->create();

        foreach (Booking::STATUSES as $index => $status) {
            Booking::factory()
                ->for($customer)
                ->for(Item::factory()->create(['name' => "Lifecycle Item {$status}"]))
                ->create([
                    'status' => $status,
                    'created_at' => now()->subDays($index),
                    'updated_at' => now()->subDays($index),
                ]);
        }

        $response = $this->actingAs($customer)
            ->get(route('customer.bookings'))
            ->assertOk();

        foreach (Booking::STATUSES as $status) {
            $response
                ->assertSee("Lifecycle Item {$status}")
                ->assertSee(str($status)->replace('_', ' ')->title()->toString());
        }
    }

    public function test_customer_bookings_page_shows_empty_state(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('customer.bookings'))
            ->assertOk()
            ->assertSee('No bookings yet. Browse the catalog to submit your first rental request.')
            ->assertSee(route('customer.catalog'), false);
    }

    public function test_customer_navigation_links_to_booking_history(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeVolt('layout.navigation')
            ->assertSee('My Bookings')
            ->assertSee(route('customer.bookings'), false)
            ->assertSee('Browse catalog')
            ->assertSee(route('customer.catalog'), false);
    }

    public function test_non_customer_dashboard_does_not_show_customer_booking_link(): void
    {
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('My Bookings')
            ->assertDontSee(route('customer.bookings'), false);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('My Bookings')
            ->assertDontSee(route('customer.bookings'), false);
    }

    public function test_staff_and_admin_cannot_access_customer_booking_history(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('customer.bookings'))
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('customer.bookings'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_customer_booking_history(): void
    {
        $this->get(route('customer.bookings'))
            ->assertRedirect(route('login'));
    }
}
