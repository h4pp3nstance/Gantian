<?php

use App\Models\Booking;
use App\Models\Fine;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(): array
    {
        Gate::authorize('view-revenue-reports');

        $statusCounts = Booking::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'completedRevenue' => Booking::query()
                ->where('status', Booking::STATUS_COMPLETED)
                ->sum('total_price'),
            'paidFineRevenue' => Fine::query()
                ->where('status', Fine::STATUS_PAID)
                ->sum('fine_amount'),
            'unpaidFineTotal' => Fine::query()
                ->where('status', Fine::STATUS_UNPAID)
                ->sum('fine_amount'),
            'bookingStatusCounts' => collect(Booking::STATUSES)
                ->mapWithKeys(fn (string $status): array => [$status => (int) ($statusCounts[$status] ?? 0)]),
            'recentCompletedBookings' => Booking::query()
                ->with(['user:id,name,email', 'item:id,name'])
                ->where('status', Booking::STATUS_COMPLETED)
                ->latest()
                ->limit(6)
                ->get(),
            'recentFines' => Fine::query()
                ->with(['booking.user:id,name,email', 'booking.item:id,name'])
                ->latest()
                ->limit(6)
                ->get(),
        ];
    }

    public function money(float|int|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 2);
    }

    public function statusClass(string $status): string
    {
        return match ($status) {
            Booking::STATUS_COMPLETED, Fine::STATUS_PAID => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            Booking::STATUS_ACTIVE, Booking::STATUS_APPROVED => 'border-sky-200 bg-sky-50 text-sky-700',
            Booking::STATUS_CANCELLED, Fine::STATUS_UNPAID => 'border-red-200 bg-red-50 text-red-700',
            Fine::STATUS_WAIVED => 'border-slate-200 bg-slate-100 text-slate-600',
            default => 'border-amber-200 bg-amber-50 text-amber-700',
        };
    }
}; ?>

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
                <h1 class="text-2xl font-semibold text-slate-950">Revenue Reports</h1>
            </div>
            <p class="text-sm text-slate-500">Updated {{ now()->format('M j, Y H:i') }}</p>
        </div>
    </x-slot>

    @php($recognizedRevenue = (float) $completedRevenue + (float) $paidFineRevenue)

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Recognized revenue</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $this->money($recognizedRevenue) }}</p>
            <p class="mt-1 text-xs text-slate-500">Completed bookings plus paid fines</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Completed bookings</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $this->money($completedRevenue) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $bookingStatusCounts[Booking::STATUS_COMPLETED] }} completed orders</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Paid fines</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $this->money($paidFineRevenue) }}</p>
            <p class="mt-1 text-xs text-slate-500">Collected fine revenue</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Unpaid fines</p>
            <p class="mt-2 text-2xl font-semibold text-red-700">{{ $this->money($unpaidFineTotal) }}</p>
            <p class="mt-1 text-xs text-slate-500">Outstanding customer balance</p>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-base font-semibold text-slate-950">Booking status</h2>
            <div class="mt-4 space-y-3">
                @foreach ($bookingStatusCounts as $status => $count)
                    <div class="flex items-center justify-between gap-3">
                        <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $this->statusClass($status) }}">{{ ucfirst($status) }}</span>
                        <span class="text-sm font-semibold text-slate-950">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
                <h2 class="text-base font-semibold text-slate-950">Recent completed bookings</h2>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse ($recentCompletedBookings as $booking)
                    <article class="grid gap-3 p-4 sm:grid-cols-[minmax(0,1fr)_9rem] sm:items-center sm:p-5">
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-semibold text-slate-950">{{ $booking->item->name }}</h3>
                            <p class="mt-1 truncate text-sm text-slate-600">{{ $booking->user->name }} · {{ $booking->start_date->format('M j') }} to {{ $booking->end_date->format('M j, Y') }}</p>
                        </div>
                        <div class="sm:text-right">
                            <p class="text-sm font-semibold text-slate-950">{{ $this->money($booking->total_price) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $booking->created_at->format('M j, Y') }}</p>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-10 text-center">
                        <h3 class="text-sm font-semibold text-slate-950">No completed bookings</h3>
                        <p class="mt-1 text-sm text-slate-500">Completed rentals will appear here.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-base font-semibold text-slate-950">Recent fines</h2>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse ($recentFines as $fine)
                <article class="grid gap-3 p-4 sm:grid-cols-[minmax(0,1fr)_8rem_8rem] sm:items-center sm:p-5">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-sm font-semibold text-slate-950">{{ $fine->booking->item->name }}</h3>
                            <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $this->statusClass($fine->status) }}">{{ ucfirst($fine->status) }}</span>
                        </div>
                        <p class="mt-1 line-clamp-1 text-sm text-slate-600">{{ $fine->reason }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $fine->booking->user->name }}</p>
                    </div>
                    <p class="text-sm font-semibold text-slate-950 sm:text-right">{{ $this->money($fine->fine_amount) }}</p>
                    <p class="text-xs text-slate-500 sm:text-right">{{ $fine->created_at->format('M j, Y') }}</p>
                </article>
            @empty
                <div class="px-5 py-10 text-center">
                    <h3 class="text-sm font-semibold text-slate-950">No fines recorded</h3>
                    <p class="mt-1 text-sm text-slate-500">Fine activity will appear after assessments are created.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
