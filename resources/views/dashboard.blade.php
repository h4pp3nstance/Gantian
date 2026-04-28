@php
    use App\Models\Booking;
    use App\Models\Fine;
    use App\Models\Item;
    use App\Models\User;

    $user = auth()->user();
    $money = fn (float|int|string $amount): string => 'Rp '.number_format((float) $amount, 2);

    $cards = [];
    $actions = [];

    if ($user->isCustomer()) {
        $cards = [
            ['label' => 'My requests', 'value' => Booking::whereBelongsTo($user)->count()],
            ['label' => 'Pending review', 'value' => Booking::whereBelongsTo($user)->where('status', Booking::STATUS_PENDING)->count()],
            ['label' => 'Active rentals', 'value' => Booking::whereBelongsTo($user)->where('status', Booking::STATUS_ACTIVE)->count()],
        ];

        $actions[] = ['label' => 'Browse catalog', 'route' => route('customer.catalog')];
    }

    if ($user->canActAs(User::ROLE_STAFF)) {
        $cards = [
            ['label' => 'Pending approvals', 'value' => Booking::where('status', Booking::STATUS_PENDING)->count()],
            ['label' => 'Ready for checkout', 'value' => Booking::where('status', Booking::STATUS_APPROVED)->count()],
            ['label' => 'Active rentals', 'value' => Booking::where('status', Booking::STATUS_ACTIVE)->count()],
            ['label' => 'Unpaid fines', 'value' => Fine::where('status', Fine::STATUS_UNPAID)->count()],
        ];

        $actions[] = ['label' => 'Open operations', 'route' => route('staff.bookings')];
    }

    if ($user->isAdmin()) {
        $cards[] = ['label' => 'Catalog items', 'value' => Item::count()];
        $cards[] = ['label' => 'Completed revenue', 'value' => $money(Booking::where('status', Booking::STATUS_COMPLETED)->sum('total_price'))];
        $actions[] = ['label' => 'Manage items', 'route' => route('admin.items')];
        $actions[] = ['label' => 'View reports', 'route' => route('admin.reports')];
    }

    $recentBookings = Booking::query()
        ->with(['user', 'item'])
        ->when($user->isCustomer(), fn ($query) => $query->whereBelongsTo($user))
        ->latest()
        ->limit(6)
        ->get();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-slate-500">{{ ucfirst($user->role) }} workspace</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-950">Operations overview</h1>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($actions as $action)
                    <a href="{{ $action['route'] }}" class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2" wire:navigate>
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($cards as $card)
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold tracking-normal text-slate-950">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        <section class="mt-8 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Recent bookings</h2>
                <p class="mt-1 text-sm text-slate-500">Latest rental activity available to your role.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($recentBookings as $booking)
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[1fr_auto] md:items-center">
                        <div>
                            <p class="text-sm font-semibold text-slate-950">{{ $booking->item->name }}</p>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $booking->user->name }} · {{ $booking->start_date->toFormattedDateString() }} to {{ $booking->end_date->toFormattedDateString() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3 md:justify-end">
                            <span class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $booking->status }}</span>
                            <span class="text-sm font-semibold text-slate-950">{{ $money($booking->total_price) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm font-medium text-slate-600">No bookings yet.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
