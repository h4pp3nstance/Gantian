<?php

use App\Models\Item;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public array $form = [
        'name' => '',
        'description' => '',
        'price_per_day' => '',
        'stock' => 1,
        'status' => Item::STATUS_AVAILABLE,
    ];

    public ?int $editingItemId = null;

    public array $editForm = [
        'name' => '',
        'description' => '',
        'price_per_day' => '',
        'stock' => 1,
        'status' => Item::STATUS_AVAILABLE,
    ];

    public ?string $notice = null;

    public ?string $error = null;

    public function with(): array
    {
        return [
            'items' => Item::query()
                ->withCount('bookings')
                ->orderBy('name')
                ->get(),
            'statuses' => Item::STATUSES,
        ];
    }

    public function createItem(): void
    {
        Gate::authorize('manage-catalog');

        $validated = $this->validate($this->rules('form'))['form'];

        Item::create($validated);

        $this->resetForm();
        $this->notice = 'Item created.';
        $this->error = null;
    }

    public function editItem(int $itemId): void
    {
        Gate::authorize('manage-catalog');

        $item = Item::query()->findOrFail($itemId);

        $this->editingItemId = $item->id;
        $this->editForm = [
            'name' => $item->name,
            'description' => $item->description ?? '',
            'price_per_day' => (string) $item->price_per_day,
            'stock' => $item->stock,
            'status' => $item->status,
        ];
        $this->notice = null;
        $this->error = null;
        $this->resetValidation();
    }

    public function updateItem(): void
    {
        Gate::authorize('manage-catalog');

        if ($this->editingItemId === null) {
            return;
        }

        $validated = $this->validate($this->rules('editForm'))['editForm'];

        Item::query()->findOrFail($this->editingItemId)->update($validated);

        $this->cancelEdit();
        $this->notice = 'Item updated.';
        $this->error = null;
    }

    public function deleteItem(int $itemId): void
    {
        Gate::authorize('manage-catalog');

        $item = Item::query()->withCount('bookings')->findOrFail($itemId);

        if ($item->bookings_count > 0) {
            $this->notice = null;
            $this->error = 'This item cannot be deleted because it already has bookings.';

            return;
        }

        $item->delete();

        if ($this->editingItemId === $itemId) {
            $this->cancelEdit();
        }

        $this->notice = 'Item deleted.';
        $this->error = null;
    }

    public function cancelEdit(): void
    {
        $this->editingItemId = null;
        $this->editForm = [
            'name' => '',
            'description' => '',
            'price_per_day' => '',
            'stock' => 1,
            'status' => Item::STATUS_AVAILABLE,
        ];
        $this->resetValidation();
    }

    public function statusClass(string $status): string
    {
        return match ($status) {
            Item::STATUS_AVAILABLE => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            Item::STATUS_MAINTENANCE => 'border-amber-200 bg-amber-50 text-amber-700',
            default => 'border-slate-200 bg-slate-100 text-slate-600',
        };
    }

    public function money(float|int|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 2);
    }

    protected function rules(string $root): array
    {
        return [
            "{$root}.name" => ['required', 'string', 'max:255'],
            "{$root}.description" => ['nullable', 'string', 'max:2000'],
            "{$root}.price_per_day" => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            "{$root}.stock" => ['required', 'integer', 'min:0', 'max:1000000'],
            "{$root}.status" => ['required', Rule::in(Item::STATUSES)],
        ];
    }

    private function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'description' => '',
            'price_per_day' => '',
            'stock' => 1,
            'status' => Item::STATUS_AVAILABLE,
        ];
        $this->resetValidation();
    }
}; ?>

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
                <h1 class="text-2xl font-semibold text-slate-950">Item Catalog</h1>
            </div>
            <p class="text-sm text-slate-500">{{ $items->count() }} items managed</p>
        </div>
    </x-slot>

    @if ($notice)
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
            {{ $notice }}
        </div>
    @endif

    @if ($error)
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">
            {{ $error }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-start">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
                <h2 class="text-base font-semibold text-slate-950">Catalog inventory</h2>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse ($items as $item)
                    <article class="p-4 sm:p-5">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-sm font-semibold text-slate-950">{{ $item->name }}</h3>
                                    <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $this->statusClass($item->status) }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </div>
                                <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $item->description ?: 'No description provided.' }}</p>
                                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Stock</dt>
                                        <dd class="mt-1 font-semibold text-slate-900">{{ $item->stock }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Price/day</dt>
                                        <dd class="mt-1 font-semibold text-slate-900">{{ $this->money($item->price_per_day) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Bookings</dt>
                                        <dd class="mt-1 font-semibold text-slate-900">{{ $item->bookings_count }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Updated</dt>
                                        <dd class="mt-1 font-semibold text-slate-900">{{ $item->updated_at->format('M j') }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="flex gap-2 xl:shrink-0">
                                <button type="button" wire:click="editItem({{ $item->id }})" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                                    Edit
                                </button>
                                <button type="button" wire:click="deleteItem({{ $item->id }})" wire:confirm="Delete {{ $item->name }}?" class="rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <h3 class="text-sm font-semibold text-slate-950">No items yet</h3>
                        <p class="mt-1 text-sm text-slate-500">Create the first catalog item from the panel.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-base font-semibold text-slate-950">{{ $editingItemId ? 'Edit item' : 'Create item' }}</h2>

            <form class="mt-4 space-y-4" wire:submit="{{ $editingItemId ? 'updateItem' : 'createItem' }}">
                @php($root = $editingItemId ? 'editForm' : 'form')

                <div>
                    <label for="{{ $root }}-name" class="block text-sm font-medium text-slate-700">Name</label>
                    <input id="{{ $root }}-name" type="text" wire:model="{{ $root }}.name" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" autocomplete="off">
                    <x-input-error :messages="$errors->get($root.'.name')" class="mt-2" />
                </div>

                <div>
                    <label for="{{ $root }}-description" class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="{{ $root }}-description" wire:model="{{ $root }}.description" rows="4" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"></textarea>
                    <x-input-error :messages="$errors->get($root.'.description')" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    <div>
                        <label for="{{ $root }}-price" class="block text-sm font-medium text-slate-700">Price per day</label>
                        <input id="{{ $root }}-price" type="number" min="0.01" step="0.01" wire:model="{{ $root }}.price_per_day" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <x-input-error :messages="$errors->get($root.'.price_per_day')" class="mt-2" />
                    </div>

                    <div>
                        <label for="{{ $root }}-stock" class="block text-sm font-medium text-slate-700">Stock</label>
                        <input id="{{ $root }}-stock" type="number" min="0" step="1" wire:model="{{ $root }}.stock" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <x-input-error :messages="$errors->get($root.'.stock')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <label for="{{ $root }}-status" class="block text-sm font-medium text-slate-700">Status</label>
                    <select id="{{ $root }}-status" wire:model="{{ $root }}.status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get($root.'.status')" class="mt-2" />
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="submit" class="inline-flex justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                        {{ $editingItemId ? 'Save changes' : 'Create item' }}
                    </button>

                    @if ($editingItemId)
                        <button type="button" wire:click="cancelEdit" class="inline-flex justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                            Cancel
                        </button>
                    @endif
                </div>
            </form>
        </aside>
    </div>
</div>
