<?php

use App\Models\Booking;
use App\Models\Item;
use App\Services\BookingAvailabilityService;
use App\Services\BookingPricingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $search = '';

    public string $statusFilter = Item::STATUS_AVAILABLE;

    public ?int $selectedItemId = null;

    public string $startDate = '';

    public string $endDate = '';

    /**
     * @var array{id: int, item: string, start_date: string, end_date: string, total_price: string}|null
     */
    public ?array $bookingSummary = null;

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'items' => $this->items(),
            'selectedItem' => $this->selectedItem(),
            'statusOptions' => [
                'all' => 'All statuses',
                Item::STATUS_AVAILABLE => 'Available',
                Item::STATUS_UNAVAILABLE => 'Unavailable',
                Item::STATUS_MAINTENANCE => 'Maintenance',
            ],
        ];
    }

    public function selectItem(int $itemId): void
    {
        $this->selectedItemId = $itemId;
        $this->bookingSummary = null;
        $this->resetErrorBag();
    }

    public function submitBooking(
        BookingPricingService $pricingService,
        BookingAvailabilityService $availabilityService
    ): void {
        Gate::authorize('submit-bookings');

        $validated = $this->validate([
            'selectedItemId' => ['required', 'integer', Rule::exists(Item::class, 'id')],
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ], [
            'selectedItemId.required' => 'Choose an item before submitting a booking request.',
            'startDate.after_or_equal' => 'Start date must be today or later.',
            'endDate.after_or_equal' => 'End date must be on or after the start date.',
        ]);

        $booking = DB::transaction(function () use ($validated, $pricingService, $availabilityService): Booking|null {
            $item = Item::query()
                ->whereKey($validated['selectedItemId'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->isBookable($item)) {
                $this->addError('selectedItemId', 'This item is not available for booking.');

                return null;
            }

            if (! $availabilityService->hasAvailability($item, $validated['startDate'], $validated['endDate'])) {
                $this->addError('startDate', 'This item is fully booked for the selected dates.');

                return null;
            }

            if ($availabilityService->hasDuplicateOpenRequest(Auth::user(), $item, $validated['startDate'], $validated['endDate'])) {
                $this->addError('startDate', 'You already have an open request for this item and date range.');

                return null;
            }

            return Auth::user()->bookings()->create([
                'item_id' => $item->id,
                'start_date' => $validated['startDate'],
                'end_date' => $validated['endDate'],
                'total_price' => $pricingService->totalPrice($item, $validated['startDate'], $validated['endDate']),
                'status' => Booking::STATUS_PENDING,
            ]);
        });

        if ($booking === null) {
            return;
        }

        $item = $booking->item;

        $this->bookingSummary = [
            'id' => $booking->id,
            'item' => $item->name,
            'start_date' => $booking->start_date->toDateString(),
            'end_date' => $booking->end_date->toDateString(),
            'total_price' => $this->formatMoney($booking->total_price),
        ];

        $this->selectedItemId = null;
        $this->startDate = '';
        $this->endDate = '';
    }

    public function isBookable(Item $item): bool
    {
        return $item->status === Item::STATUS_AVAILABLE && $item->stock > 0;
    }

    public function formatMoney(int|float|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 2);
    }

    public function statusLabel(string $status): string
    {
        return str($status)->replace('_', ' ')->title()->toString();
    }

    /**
     * @return Collection<int, Item>
     */
    private function items(): Collection
    {
        return Item::query()
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(trim($this->search) !== '', function ($query): void {
                $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($this->search)).'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->orderByRaw("case when status = 'available' and stock > 0 then 0 else 1 end")
            ->orderBy('name')
            ->get();
    }

    private function selectedItem(): ?Item
    {
        if ($this->selectedItemId === null) {
            return null;
        }

        return Item::query()->find($this->selectedItemId);
    }
}; ?>

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h1 class="text-xl font-semibold text-slate-900">Customer Catalog</h1>
            <p class="text-sm text-slate-600">Browse available equipment and submit a rental request.</p>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="space-y-5">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_14rem]">
                    <div>
                        <label for="catalog-search" class="block text-sm font-medium text-slate-700">Search catalog</label>
                        <input
                            wire:model.live.debounce.300ms="search"
                            id="catalog-search"
                            type="search"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            autocomplete="off"
                        >
                    </div>

                    <div>
                        <label for="status-filter" class="block text-sm font-medium text-slate-700">Status</label>
                        <select
                            wire:model.live="statusFilter"
                            id="status-filter"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($items as $item)
                    <article
                        wire:key="catalog-item-{{ $item->id }}"
                        class="rounded-lg border bg-white p-5 shadow-sm transition {{ $selectedItemId === $item->id ? 'border-indigo-500 ring-2 ring-indigo-100' : 'border-slate-200' }}"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h2 class="text-base font-semibold text-slate-950">{{ $item->name }}</h2>
                                <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">
                                    {{ $item->description ?: 'No description provided.' }}
                                </p>
                            </div>

                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $this->isBookable($item) ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200' }}">
                                {{ $this->statusLabel($item->status) }}
                            </span>
                        </div>

                        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                                <dt class="text-xs font-medium uppercase text-slate-500">Stock</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $item->stock }}</dd>
                            </div>
                            <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
                                <dt class="text-xs font-medium uppercase text-slate-500">Daily price</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $this->formatMoney($item->price_per_day) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-5 flex items-center justify-between gap-3">
                            <p class="text-xs text-slate-500">
                                {{ $this->isBookable($item) ? 'Ready for request' : 'Not accepting requests' }}
                            </p>

                            <button
                                wire:click="selectItem({{ $item->id }})"
                                type="button"
                                @disabled(! $this->isBookable($item))
                                class="inline-flex items-center rounded-md border border-transparent bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"
                            >
                                {{ $selectedItemId === $item->id ? 'Selected' : 'Select' }}
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center md:col-span-2">
                        <h2 class="text-base font-semibold text-slate-900">No items found</h2>
                        <p class="mt-2 text-sm text-slate-600">Adjust the search or status filter to view more catalog items.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="lg:sticky lg:top-8 lg:self-start">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-950">Booking Request</h2>

                @if ($selectedItem)
                    <div class="mt-4 rounded-md border border-indigo-100 bg-indigo-50 px-3 py-2">
                        <p class="text-sm font-medium text-indigo-950">{{ $selectedItem->name }}</p>
                        <p class="mt-1 text-xs text-indigo-700">{{ $this->formatMoney($selectedItem->price_per_day) }} per day</p>
                    </div>
                @else
                    <p class="mt-4 text-sm leading-6 text-slate-600">Select an available item to prepare a booking request.</p>
                @endif

                <form wire:submit="submitBooking" class="mt-5 space-y-4">
                    <div>
                        <label for="start-date" class="block text-sm font-medium text-slate-700">Start date</label>
                        <input
                            wire:model="startDate"
                            id="start-date"
                            type="date"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                        <x-input-error :messages="$errors->get('startDate')" class="mt-2" />
                    </div>

                    <div>
                        <label for="end-date" class="block text-sm font-medium text-slate-700">End date</label>
                        <input
                            wire:model="endDate"
                            id="end-date"
                            type="date"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                        <x-input-error :messages="$errors->get('endDate')" class="mt-2" />
                    </div>

                    <x-input-error :messages="$errors->get('selectedItemId')" />

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Submit request
                    </button>
                </form>

                @if ($bookingSummary)
                    <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 p-4">
                        <h3 class="text-sm font-semibold text-emerald-950">Request submitted</h3>
                        <dl class="mt-3 space-y-2 text-sm text-emerald-900">
                            <div class="flex justify-between gap-4">
                                <dt>Booking</dt>
                                <dd class="font-medium">#{{ $bookingSummary['id'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt>Item</dt>
                                <dd class="text-right font-medium">{{ $bookingSummary['item'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt>Dates</dt>
                                <dd class="text-right font-medium">{{ $bookingSummary['start_date'] }} to {{ $bookingSummary['end_date'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt>Total</dt>
                                <dd class="font-semibold">{{ $bookingSummary['total_price'] }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>
