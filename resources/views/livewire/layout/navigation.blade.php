<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex min-w-0">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md bg-slate-950 text-sm font-semibold text-white">G</span>
                        <span class="hidden text-sm font-semibold tracking-normal text-slate-900 sm:block">Gantian</span>
                    </a>
                </div>

                <div class="hidden space-x-7 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Overview') }}
                    </x-nav-link>

                    @if (auth()->user()->isCustomer())
                        <x-nav-link :href="route('customer.catalog')" :active="request()->routeIs('customer.catalog')" wire:navigate>
                            {{ __('Catalog') }}
                        </x-nav-link>
                    @endif

                    @if (auth()->user()->canActAs(\App\Models\User::ROLE_STAFF))
                        <x-nav-link :href="route('staff.bookings')" :active="request()->routeIs('staff.bookings')" wire:navigate>
                            {{ __('Operations') }}
                        </x-nav-link>
                    @endif

                    @if (auth()->user()->isAdmin())
                        <x-nav-link :href="route('admin.items')" :active="request()->routeIs('admin.items')" wire:navigate>
                            {{ __('Items') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.reports')" :active="request()->routeIs('admin.reports')" wire:navigate>
                            {{ __('Reports') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium leading-4 text-slate-600 transition hover:border-slate-300 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                            <div>
                                <div class="text-left text-sm font-semibold text-slate-900" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                                <div class="text-left text-xs font-medium uppercase tracking-wide text-slate-500">{{ auth()->user()->role }}</div>
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2" aria-label="Toggle navigation">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Overview') }}
            </x-responsive-nav-link>

            @if (auth()->user()->isCustomer())
                <x-responsive-nav-link :href="route('customer.catalog')" :active="request()->routeIs('customer.catalog')" wire:navigate>
                    {{ __('Catalog') }}
                </x-responsive-nav-link>
            @endif

            @if (auth()->user()->canActAs(\App\Models\User::ROLE_STAFF))
                <x-responsive-nav-link :href="route('staff.bookings')" :active="request()->routeIs('staff.bookings')" wire:navigate>
                    {{ __('Operations') }}
                </x-responsive-nav-link>
            @endif

            @if (auth()->user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.items')" :active="request()->routeIs('admin.items')" wire:navigate>
                    {{ __('Items') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.reports')" :active="request()->routeIs('admin.reports')" wire:navigate>
                    {{ __('Reports') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-slate-200">
            <div class="px-4">
                <div class="font-semibold text-base text-slate-900" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-slate-500">{{ auth()->user()->email }}</div>
                <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ auth()->user()->role }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
