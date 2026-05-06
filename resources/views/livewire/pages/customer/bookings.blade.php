<?php

use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $statusCounts = Booking::query()
            ->whereBelongsTo(Auth::user())
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'bookings' => Booking::query()
                ->with(['item', 'fines'])
                ->whereBelongsTo(Auth::user())
                ->latest()
                ->get(),
            'statusCounts' => collect(Booking::STATUSES)
                ->mapWithKeys(fn (string $status): array => [$status => (int) ($statusCounts[$status] ?? 0)]),
        ];
    }

    public function money(float|int|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 2);
    }

    public function statusLabel(string $status): string
    {
        return str($status)->replace('_', ' ')->title()->toString();
    }

    public function statusClass(string $status): string
    {
        return match ($status) {
            Booking::STATUS_PENDING => 'border-amber-200 bg-amber-50 text-amber-700',
            Booking::STATUS_APPROVED => 'border-sky-200 bg-sky-50 text-sky-700',
            Booking::STATUS_ACTIVE => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            Booking::STATUS_COMPLETED => 'border-slate-200 bg-slate-100 text-slate-600',
            Booking::STATUS_CANCELLED => 'border-rose-200 bg-rose-50 text-rose-700',
            default => 'border-slate-200 bg-slate-100 text-slate-600',
        };
    }
}; ?>

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</p>
                <h1 class="text-2xl font-semibold text-slate-950">My Bookings</h1>
                <p class="mt-1 text-sm text-slate-600">Track your rental requests and current booking status.</p>
            </div>
            <a href="{{ route('customer.catalog') }}" class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2" wire:navigate>
                Browse catalog
            </a>
        </div>
    </x-slot>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($statusCounts as $status => $count)
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium text-slate-500">{{ $this->statusLabel($status) }}</p>
                    <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $this->statusClass($status) }}">{{ $count }}</span>
                </div>
                <p class="mt-3 text-2xl font-semibold text-slate-950">{{ $count }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ str($status)->replace('_', ' ') }} bookings</p>
            </div>
        @endforeach
    </section>

    <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-base font-semibold text-slate-950">Booking history</h2>
            <p class="mt-1 text-sm text-slate-500">Newest requests and rentals appear first.</p>
        </div>

        <div class="divide-y divide-slate-200">
            @forelse ($bookings as $booking)
                @php($fineTotal = $booking->fines->sum('fine_amount'))

                <article class="grid gap-4 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_10rem_9rem_10rem] lg:items-center">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-sm font-semibold text-slate-950">{{ $booking->item->name }}</h3>
                            <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $this->statusClass($booking->status) }}">{{ $this->statusLabel($booking->status) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $booking->start_date->toFormattedDateString() }} to {{ $booking->end_date->toFormattedDateString() }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Submitted {{ $booking->created_at->format('M j, Y') }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total price</p>
                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ $this->money($booking->total_price) }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</p>
                        <p class="mt-1 text-sm font-semibold text-slate-950">{{ $this->statusLabel($booking->status) }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Fines</p>
                        @if ($booking->fines->isNotEmpty())
                            <p class="mt-1 text-sm font-semibold text-rose-700">{{ $booking->fines->count() }} fine(s)</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $this->money($fineTotal) }}</p>
                        @else
                            <p class="mt-1 text-sm font-semibold text-slate-950">None</p>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center">
                    <h3 class="text-sm font-semibold text-slate-950">No bookings yet.</h3>
                    <p class="mt-2 text-sm text-slate-600">No bookings yet. Browse the catalog to submit your first rental request.</p>
                    <a href="{{ route('customer.catalog') }}" class="mt-5 inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" wire:navigate>
                        Browse catalog
                    </a>
                </div>
            @endforelse
        </div>
    </section>
</div>
