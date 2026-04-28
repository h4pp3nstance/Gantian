<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Fine;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = $this->seedUsers();

        if (! $this->domainSchemaIsAvailable()) {
            return;
        }

        $items = $this->seedItems();
        $bookings = $this->seedBookings($users, $items);
        $this->seedFines($bookings);
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(): array
    {
        $users = [];
        $hasRole = Schema::hasColumn('users', 'role');

        foreach ([
            'admin' => ['name' => 'Gantian Admin', 'email' => 'admin@gantian.test', 'role' => User::ROLE_ADMIN],
            'staff' => ['name' => 'Gantian Staff', 'email' => 'staff@gantian.test', 'role' => User::ROLE_STAFF],
            'customer' => ['name' => 'Dina Customer', 'email' => 'customer@gantian.test', 'role' => User::ROLE_CUSTOMER],
            'customer_two' => ['name' => 'Rafi Customer', 'email' => 'customer2@gantian.test', 'role' => User::ROLE_CUSTOMER],
        ] as $key => $attributes) {
            if (! $hasRole) {
                unset($attributes['role']);
            }

            $factoryAttributes = User::factory()->make([
                'email_verified_at' => Carbon::parse('2026-01-01 00:00:00'),
                'remember_token' => null,
            ])->getAttributes();

            $users[$key] = $this->persist(User::class, $attributes + $factoryAttributes, [
                'email' => $attributes['email'],
            ]);
        }

        return $users;
    }

    private function domainSchemaIsAvailable(): bool
    {
        return class_exists(Item::class)
            && class_exists(Booking::class)
            && class_exists(Fine::class)
            && Schema::hasTable('items')
            && Schema::hasTable('bookings')
            && Schema::hasTable('fines');
    }

    /**
     * @return array<string, Item>
     */
    private function seedItems(): array
    {
        $items = [];

        foreach ([
            'camera' => [
                'name' => 'Sony Alpha A6400 Mirrorless Camera',
                'description' => 'Compact camera kit with 16-50mm lens for product and travel shoots.',
                'price_per_day' => 175000,
                'stock' => 3,
                'status' => Item::STATUS_AVAILABLE,
            ],
            'projector' => [
                'name' => 'Epson EB-X500 Meeting Projector',
                'description' => 'Bright XGA projector with HDMI cable and carrying case.',
                'price_per_day' => 125000,
                'stock' => 2,
                'status' => Item::STATUS_AVAILABLE,
            ],
            'speaker' => [
                'name' => 'JBL PartyBox 310 Speaker',
                'description' => 'Portable event speaker with microphone input and rolling handle.',
                'price_per_day' => 225000,
                'stock' => 1,
                'status' => Item::STATUS_UNAVAILABLE,
            ],
            'tripod' => [
                'name' => 'Manfrotto Compact Tripod',
                'description' => 'Lightweight tripod for camera or mobile production setups.',
                'price_per_day' => 50000,
                'stock' => 5,
                'status' => Item::STATUS_AVAILABLE,
            ],
            'drone' => [
                'name' => 'DJI Mini 4 Pro Drone',
                'description' => 'Aerial video kit with two batteries, charger, and controller.',
                'price_per_day' => 300000,
                'stock' => 0,
                'status' => Item::STATUS_MAINTENANCE,
            ],
        ] as $key => $attributes) {
            $items[$key] = $this->persist(Item::class, $attributes, ['name' => $attributes['name']]);
        }

        return $items;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Item>  $items
     * @return array<string, Booking>
     */
    private function seedBookings(array $users, array $items): array
    {
        $bookings = [];

        foreach ([
            'pending_camera' => [
                'user_id' => $users['customer']->id,
                'item_id' => $items['camera']->id,
                'start_date' => Carbon::parse('2026-05-02'),
                'end_date' => Carbon::parse('2026-05-04'),
                'total_price' => 525000,
                'status' => Booking::STATUS_PENDING,
            ],
            'approved_projector' => [
                'user_id' => $users['customer_two']->id,
                'item_id' => $items['projector']->id,
                'start_date' => Carbon::parse('2026-05-06'),
                'end_date' => Carbon::parse('2026-05-07'),
                'total_price' => 250000,
                'status' => Booking::STATUS_APPROVED,
            ],
            'active_speaker' => [
                'user_id' => $users['customer']->id,
                'item_id' => $items['speaker']->id,
                'start_date' => Carbon::parse('2026-04-27'),
                'end_date' => Carbon::parse('2026-04-29'),
                'total_price' => 675000,
                'status' => Booking::STATUS_ACTIVE,
            ],
            'completed_tripod' => [
                'user_id' => $users['customer_two']->id,
                'item_id' => $items['tripod']->id,
                'start_date' => Carbon::parse('2026-04-10'),
                'end_date' => Carbon::parse('2026-04-12'),
                'total_price' => 150000,
                'status' => Booking::STATUS_COMPLETED,
            ],
            'cancelled_drone' => [
                'user_id' => $users['customer']->id,
                'item_id' => $items['drone']->id,
                'start_date' => Carbon::parse('2026-05-12'),
                'end_date' => Carbon::parse('2026-05-13'),
                'total_price' => 600000,
                'status' => Booking::STATUS_CANCELLED,
            ],
        ] as $key => $attributes) {
            $bookings[$key] = $this->persist(Booking::class, $attributes,
                [
                    'user_id' => $attributes['user_id'],
                    'item_id' => $attributes['item_id'],
                    'start_date' => $attributes['start_date'],
                ]
            );
        }

        return $bookings;
    }

    /**
     * @param  array<string, Booking>  $bookings
     */
    private function seedFines(array $bookings): void
    {
        foreach ([
            [
                'booking_id' => $bookings['completed_tripod']->id,
                'fine_amount' => 25000,
                'reason' => 'Returned one day late after the scheduled end date.',
                'status' => Fine::STATUS_PAID,
            ],
            [
                'booking_id' => $bookings['active_speaker']->id,
                'fine_amount' => 50000,
                'reason' => 'Protective cover was reported missing during active rental.',
                'status' => Fine::STATUS_UNPAID,
            ],
        ] as $attributes) {
            $this->persist(Fine::class, $attributes,
                [
                    'booking_id' => $attributes['booking_id'],
                    'reason' => $attributes['reason'],
                ]
            );
        }
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $uniqueBy
     * @return TModel
     */
    private function persist(string $modelClass, array $attributes, array $uniqueBy): Model
    {
        $record = $modelClass::query()->firstOrNew($uniqueBy);
        $record->forceFill($attributes)->save();

        return $record;
    }
}
