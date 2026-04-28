<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class CustomerCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_catalog_items(): void
    {
        $customer = User::factory()->customer()->create();
        $camera = Item::factory()->available()->create([
            'name' => 'Cinema Camera',
            'description' => 'Production ready camera kit.',
            'price_per_day' => '75000.00',
            'stock' => 3,
        ]);

        Item::factory()->maintenance()->create([
            'name' => 'Lighting Kit',
        ]);

        $this->actingAs($customer)
            ->get(route('customer.catalog'))
            ->assertOk()
            ->assertSeeVolt('pages.customer.catalog')
            ->assertSee($camera->name)
            ->assertSee('Production ready camera kit.')
            ->assertSee('Rp 75,000.00')
            ->assertDontSee('Lighting Kit');
    }

    public function test_customer_can_submit_pending_booking_request(): void
    {
        $customer = User::factory()->customer()->create();
        $item = Item::factory()->available()->create([
            'name' => 'Tripod Set',
            'price_per_day' => '100000.00',
            'stock' => 2,
        ]);

        $this->actingAs($customer);

        Volt::test('pages.customer.catalog')
            ->call('selectItem', $item->id)
            ->set('startDate', now()->toDateString())
            ->set('endDate', now()->addDays(2)->toDateString())
            ->call('submitBooking')
            ->assertHasNoErrors()
            ->assertSee('Request submitted')
            ->assertSee('Rp 300,000.00');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $customer->id,
            'item_id' => $item->id,
            'status' => Booking::STATUS_PENDING,
            'total_price' => '300000.00',
        ]);
    }

    public function test_customer_cannot_request_dates_when_reserved_bookings_consume_stock(): void
    {
        $customer = User::factory()->customer()->create();
        $item = Item::factory()->available()->create([
            'price_per_day' => '100000.00',
            'stock' => 1,
        ]);

        Booking::factory()
            ->approved()
            ->for($item)
            ->create([
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
            ]);

        $this->actingAs($customer);

        Volt::test('pages.customer.catalog')
            ->set('selectedItemId', $item->id)
            ->set('startDate', now()->addDay()->toDateString())
            ->set('endDate', now()->addDays(3)->toDateString())
            ->call('submitBooking')
            ->assertHasErrors(['startDate'])
            ->assertSee('This item is fully booked for the selected dates.');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_customer_can_submit_when_only_pending_bookings_overlap(): void
    {
        $customer = User::factory()->customer()->create();
        $item = Item::factory()->available()->create([
            'price_per_day' => '100000.00',
            'stock' => 1,
        ]);

        Booking::factory()
            ->pending()
            ->for($item)
            ->create([
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
            ]);

        $this->actingAs($customer);

        Volt::test('pages.customer.catalog')
            ->set('selectedItemId', $item->id)
            ->set('startDate', now()->addDay()->toDateString())
            ->set('endDate', now()->addDays(3)->toDateString())
            ->call('submitBooking')
            ->assertHasNoErrors()
            ->assertSee('Request submitted');

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_customer_cannot_submit_duplicate_open_request_for_same_item_and_dates(): void
    {
        $customer = User::factory()->customer()->create();
        $item = Item::factory()->available()->create([
            'price_per_day' => '100000.00',
            'stock' => 2,
        ]);
        $startDate = now()->toDateString();
        $endDate = now()->addDays(2)->toDateString();

        Booking::factory()
            ->pending()
            ->for($customer)
            ->for($item)
            ->create([
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

        $this->actingAs($customer);

        Volt::test('pages.customer.catalog')
            ->set('selectedItemId', $item->id)
            ->set('startDate', $startDate)
            ->set('endDate', $endDate)
            ->call('submitBooking')
            ->assertHasErrors(['startDate'])
            ->assertSee('You already have an open request for this item and date range.');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_customer_cannot_submit_duplicate_approved_request_for_same_item_and_dates(): void
    {
        $customer = User::factory()->customer()->create();
        $item = Item::factory()->available()->create([
            'price_per_day' => '100000.00',
            'stock' => 2,
        ]);
        $startDate = now()->toDateString();
        $endDate = now()->addDays(2)->toDateString();

        Booking::factory()
            ->approved()
            ->for($customer)
            ->for($item)
            ->create([
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

        $this->actingAs($customer);

        Volt::test('pages.customer.catalog')
            ->set('selectedItemId', $item->id)
            ->set('startDate', $startDate)
            ->set('endDate', $endDate)
            ->call('submitBooking')
            ->assertHasErrors(['startDate'])
            ->assertSee('You already have an open request for this item and date range.');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_request_rejects_items_that_are_not_bookable(): void
    {
        $this->actingAs(User::factory()->customer()->create());

        $item = Item::factory()->unavailable()->create([
            'stock' => 5,
        ]);

        Volt::test('pages.customer.catalog')
            ->set('selectedItemId', $item->id)
            ->set('startDate', now()->toDateString())
            ->set('endDate', now()->toDateString())
            ->call('submitBooking')
            ->assertHasErrors(['selectedItemId']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_request_rejects_zero_stock_items(): void
    {
        $this->actingAs(User::factory()->customer()->create());

        $item = Item::factory()->available()->create([
            'stock' => 0,
        ]);

        Volt::test('pages.customer.catalog')
            ->set('selectedItemId', $item->id)
            ->set('startDate', now()->toDateString())
            ->set('endDate', now()->toDateString())
            ->call('submitBooking')
            ->assertHasErrors(['selectedItemId']);

        $this->assertDatabaseCount('bookings', 0);
    }
}
