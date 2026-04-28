<?php

use App\Models\Booking;
use App\Services\BookingLifecycleService;
use App\Services\FineAssessmentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /** @var array<int, string> */
    public array $fineAmounts = [];

    /** @var array<int, string> */
    public array $fineReasons = [];

    public ?string $success = null;

    public ?string $error = null;

    public function with(): array
    {
        return [
            'bookings' => Booking::query()
                ->with(['user', 'item', 'fines'])
                ->latest()
                ->get(),
        ];
    }

    public function approve(int $bookingId): void
    {
        Gate::authorize('validate-bookings');

        $this->runLifecycleAction(
            $bookingId,
            fn (BookingLifecycleService $service, Booking $booking) => $service->approve($booking),
            'Booking approved.'
        );
    }

    public function checkout(int $bookingId): void
    {
        Gate::authorize('process-checkout');

        $this->runLifecycleAction(
            $bookingId,
            fn (BookingLifecycleService $service, Booking $booking) => $service->checkout($booking),
            'Booking checked out.'
        );
    }

    public function checkin(int $bookingId): void
    {
        Gate::authorize('process-checkin');

        $this->runLifecycleAction(
            $bookingId,
            fn (BookingLifecycleService $service, Booking $booking) => $service->checkin($booking),
            'Booking checked in.'
        );
    }

    public function cancel(int $bookingId): void
    {
        Gate::authorize('validate-bookings');

        $this->runLifecycleAction(
            $bookingId,
            fn (BookingLifecycleService $service, Booking $booking) => $service->cancel($booking),
            'Booking cancelled.'
        );
    }

    public function issueFine(int $bookingId): void
    {
        Gate::authorize('issue-fines');

        $amountField = "fineAmounts.$bookingId";
        $reasonField = "fineReasons.$bookingId";

        $this->resetMessages();
        $this->validate([
            $amountField => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            $reasonField => ['required', 'string', 'max:255'],
        ], [
            "$amountField.required" => 'Fine amount is required.',
            "$amountField.regex" => 'Fine amount must be a positive decimal.',
            "$reasonField.required" => 'Fine reason is required.',
            "$reasonField.max" => 'Fine reason must be 255 characters or fewer.',
        ]);

        try {
            app(FineAssessmentService::class)->issue(
                $this->booking($bookingId),
                $this->fineAmounts[$bookingId],
                $this->fineReasons[$bookingId]
            );

            $this->fineAmounts[$bookingId] = '';
            $this->fineReasons[$bookingId] = '';
            $this->success = 'Fine issued.';
        } catch (\DomainException | \InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    public function fineTotal(Booking $booking): string
    {
        return $this->money($booking->fines->sum('fine_amount'));
    }

    public function money(float|int|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 2);
    }

    public function statusClasses(string $status): string
    {
        return match ($status) {
            Booking::STATUS_PENDING => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            Booking::STATUS_APPROVED => 'bg-blue-50 text-blue-800 ring-blue-600/20',
            Booking::STATUS_ACTIVE => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
            Booking::STATUS_COMPLETED => 'bg-slate-100 text-slate-700 ring-slate-600/20',
            Booking::STATUS_CANCELLED => 'bg-rose-50 text-rose-800 ring-rose-600/20',
            default => 'bg-gray-100 text-gray-700 ring-gray-600/20',
        };
    }

    private function runLifecycleAction(int $bookingId, \Closure $action, string $message): void
    {
        $this->resetMessages();

        try {
            $action(app(BookingLifecycleService::class), $this->booking($bookingId));
            $this->success = $message;
        } catch (\DomainException | \InvalidArgumentException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    private function booking(int $bookingId): Booking
    {
        return Booking::query()->with(['user', 'item', 'fines'])->findOrFail($bookingId);
    }

    private function resetMessages(): void
    {
        $this->success = null;
        $this->error = null;
    }
}; ?>

<div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-950">Staff bookings</h1>
                <p class="mt-1 text-sm text-gray-600">Validate requests, move rentals through checkout and check-in, and record fines.</p>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm sm:flex">
                <div class="rounded-md border border-gray-200 bg-white px-3 py-2 shadow-sm">
                    <span class="block text-xs font-medium uppercase tracking-wide text-gray-500">Open</span>
                    <span class="font-semibold text-gray-950">{{ $bookings->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_APPROVED, Booking::STATUS_ACTIVE])->count() }}</span>
                </div>
                <div class="rounded-md border border-gray-200 bg-white px-3 py-2 shadow-sm">
                    <span class="block text-xs font-medium uppercase tracking-wide text-gray-500">Fines</span>
                    <span class="font-semibold text-gray-950">{{ $bookings->sum(fn (Booking $booking) => $booking->fines->count()) }}</span>
                </div>
            </div>
        </div>

        @if ($success)
            <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                {{ $success }}
            </div>
        @endif

        @if ($error)
            <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert">
                {{ $error }}
            </div>
        @endif

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="hidden lg:block">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Booking</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Customer</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Item</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Dates</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Total</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Fines</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($bookings as $booking)
                            <tr wire:key="booking-row-{{ $booking->id }}" class="align-top">
                                <td class="px-4 py-4">
                                    <div class="font-mono text-xs text-gray-500">#{{ $booking->id }}</div>
                                    <span class="mt-2 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $this->statusClasses($booking->status) }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-gray-950">{{ $booking->user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $booking->user->email }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-gray-950">{{ $booking->item->name }}</div>
                                    <div class="text-xs text-gray-500">Stock {{ $booking->item->stock }}</div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700">
                                    <div>{{ $booking->start_date->format('M j, Y') }}</div>
                                    <div class="text-xs text-gray-500">to {{ $booking->end_date->format('M j, Y') }}</div>
                                </td>
                                <td class="px-4 py-4 text-right text-sm font-semibold text-gray-950">
                                    {{ $this->money($booking->total_price) }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $this->fineTotal($booking) }}</div>
                                    <div class="text-xs text-gray-500">{{ $booking->fines->count() }} {{ Str::plural('fine', $booking->fines->count()) }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($booking->status === Booking::STATUS_PENDING)
                                            <button type="button" wire:click="approve({{ $booking->id }})" class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Approve</button>
                                            <button type="button" wire:click="cancel({{ $booking->id }})" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Cancel</button>
                                        @elseif ($booking->status === Booking::STATUS_APPROVED)
                                            <button type="button" wire:click="checkout({{ $booking->id }})" class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Checkout</button>
                                            <button type="button" wire:click="cancel({{ $booking->id }})" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Cancel</button>
                                        @elseif ($booking->status === Booking::STATUS_ACTIVE)
                                            <button type="button" wire:click="checkin({{ $booking->id }})" class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Check in</button>
                                        @else
                                            <span class="text-xs text-gray-500">No status action</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @if (in_array($booking->status, [Booking::STATUS_ACTIVE, Booking::STATUS_COMPLETED], true))
                                <tr wire:key="fine-row-{{ $booking->id }}" class="bg-gray-50/70">
                                    <td colspan="7" class="px-4 py-3">
                                        <form wire:submit="issueFine({{ $booking->id }})" class="grid gap-3 md:grid-cols-[10rem_1fr_auto] md:items-start">
                                            <div>
                                                <label for="fine-amount-{{ $booking->id }}" class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Fine amount</label>
                                                <input id="fine-amount-{{ $booking->id }}" type="text" inputmode="decimal" wire:model="fineAmounts.{{ $booking->id }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="0.00" aria-describedby="fine-amount-error-{{ $booking->id }}">
                                                <x-input-error id="fine-amount-error-{{ $booking->id }}" :messages="$errors->get('fineAmounts.'.$booking->id)" class="mt-1" />
                                            </div>
                                            <div>
                                                <label for="fine-reason-{{ $booking->id }}" class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Reason</label>
                                                <input id="fine-reason-{{ $booking->id }}" type="text" wire:model="fineReasons.{{ $booking->id }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Late return, missing accessory, damage" aria-describedby="fine-reason-error-{{ $booking->id }}">
                                                <x-input-error id="fine-reason-error-{{ $booking->id }}" :messages="$errors->get('fineReasons.'.$booking->id)" class="mt-1" />
                                            </div>
                                            <button type="submit" class="mt-5 rounded-md bg-white px-3 py-2 text-xs font-semibold text-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 md:mt-6">Issue fine</button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">No bookings found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-200 lg:hidden">
                @forelse ($bookings as $booking)
                    <section wire:key="booking-card-{{ $booking->id }}" class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-mono text-xs text-gray-500">#{{ $booking->id }}</div>
                                <h2 class="mt-1 text-base font-semibold text-gray-950">{{ $booking->item->name }}</h2>
                                <p class="text-sm text-gray-600">{{ $booking->user->name }}</p>
                            </div>
                            <span class="inline-flex shrink-0 items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $this->statusClasses($booking->status) }}">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Dates</dt>
                                <dd class="mt-1 text-gray-900">{{ $booking->start_date->format('M j') }} - {{ $booking->end_date->format('M j, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total</dt>
                                <dd class="mt-1 font-semibold text-gray-950">{{ $this->money($booking->total_price) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fines</dt>
                                <dd class="mt-1 text-gray-900">{{ $this->fineTotal($booking) }} · {{ $booking->fines->count() }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Email</dt>
                                <dd class="mt-1 truncate text-gray-900">{{ $booking->user->email }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($booking->status === Booking::STATUS_PENDING)
                                <button type="button" wire:click="approve({{ $booking->id }})" class="rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Approve</button>
                                <button type="button" wire:click="cancel({{ $booking->id }})" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Cancel</button>
                            @elseif ($booking->status === Booking::STATUS_APPROVED)
                                <button type="button" wire:click="checkout({{ $booking->id }})" class="rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Checkout</button>
                                <button type="button" wire:click="cancel({{ $booking->id }})" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Cancel</button>
                            @elseif ($booking->status === Booking::STATUS_ACTIVE)
                                <button type="button" wire:click="checkin({{ $booking->id }})" class="rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Check in</button>
                            @endif
                        </div>

                        @if (in_array($booking->status, [Booking::STATUS_ACTIVE, Booking::STATUS_COMPLETED], true))
                            <form wire:submit="issueFine({{ $booking->id }})" class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-3">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="fine-amount-mobile-{{ $booking->id }}" class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Fine amount</label>
                                        <input id="fine-amount-mobile-{{ $booking->id }}" type="text" inputmode="decimal" wire:model="fineAmounts.{{ $booking->id }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="0.00">
                                        <x-input-error :messages="$errors->get('fineAmounts.'.$booking->id)" class="mt-1" />
                                    </div>
                                    <div>
                                        <label for="fine-reason-mobile-{{ $booking->id }}" class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Reason</label>
                                        <input id="fine-reason-mobile-{{ $booking->id }}" type="text" wire:model="fineReasons.{{ $booking->id }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-gray-900 focus:ring-gray-900" placeholder="Late return or damage">
                                        <x-input-error :messages="$errors->get('fineReasons.'.$booking->id)" class="mt-1" />
                                    </div>
                                </div>
                                <button type="submit" class="mt-3 rounded-md bg-white px-3 py-2 text-xs font-semibold text-gray-800 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">Issue fine</button>
                            </form>
                        @endif
                    </section>
                @empty
                    <div class="p-8 text-center text-sm text-gray-500">No bookings found.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
