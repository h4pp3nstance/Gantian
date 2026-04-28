<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Fine;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_catalog_items(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Volt::test('pages.admin.items')
            ->set('form.name', 'Conference Projector')
            ->set('form.description', 'Bright portable projector')
            ->set('form.price_per_day', '45.50')
            ->set('form.stock', 3)
            ->set('form.status', Item::STATUS_AVAILABLE)
            ->call('createItem')
            ->assertHasNoErrors()
            ->assertSee('Item created.');

        $item = Item::query()->where('name', 'Conference Projector')->firstOrFail();

        Volt::test('pages.admin.items')
            ->call('editItem', $item->id)
            ->set('editForm.name', 'Updated Projector')
            ->set('editForm.description', 'Updated description')
            ->set('editForm.price_per_day', '55.00')
            ->set('editForm.stock', 4)
            ->set('editForm.status', Item::STATUS_MAINTENANCE)
            ->call('updateItem')
            ->assertHasNoErrors()
            ->assertSee('Item updated.');

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Updated Projector',
            'stock' => 4,
            'status' => Item::STATUS_MAINTENANCE,
        ]);

        Volt::test('pages.admin.items')
            ->call('deleteItem', $item->id)
            ->assertSee('Item deleted.');

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_catalog_validation_and_booking_delete_guard_are_visible(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Volt::test('pages.admin.items')
            ->set('form.name', '')
            ->set('form.price_per_day', '0')
            ->set('form.stock', -1)
            ->set('form.status', 'retired')
            ->call('createItem')
            ->assertHasErrors([
                'form.name' => 'required',
                'form.price_per_day' => 'min',
                'form.stock' => 'min',
                'form.status' => 'in',
            ]);

        $item = Item::factory()->create();
        Booking::factory()->for($item)->create();

        Volt::test('pages.admin.items')
            ->call('deleteItem', $item->id)
            ->assertSee('This item cannot be deleted because it already has bookings.');

        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    public function test_admin_revenue_report_shows_aggregates_and_recent_activity(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $customer = User::factory()->customer()->create(['name' => 'Rina Customer']);
        $item = Item::factory()->create(['name' => 'Audio Kit']);

        $completedBooking = Booking::factory()
            ->for($customer)
            ->for($item)
            ->completed()
            ->create(['total_price' => 240]);

        Booking::factory()->pending()->create(['total_price' => 80]);

        Fine::factory()
            ->for($completedBooking)
            ->paid()
            ->create(['fine_amount' => 25, 'reason' => 'Late return']);

        Fine::factory()
            ->for($completedBooking)
            ->unpaid()
            ->create(['fine_amount' => 15, 'reason' => 'Missing cable']);

        $this->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('Revenue Reports')
            ->assertSee('Rp 265.00')
            ->assertSee('Rp 240.00')
            ->assertSee('Rp 25.00')
            ->assertSee('Rp 15.00')
            ->assertSee('Audio Kit')
            ->assertSee('Rina Customer')
            ->assertSee('Late return')
            ->assertSee('Missing cable');
    }

    public function test_staff_cannot_access_admin_screens(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $this->get(route('admin.items'))->assertForbidden();
        $this->get(route('admin.reports'))->assertForbidden();
    }
}
