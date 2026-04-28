<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::view('dashboard', 'dashboard')
        ->name('dashboard');

    Volt::route('catalog', 'pages.customer.catalog')
        ->middleware('role:customer')
        ->name('customer.catalog');

    Route::prefix('staff')
        ->middleware('role:staff')
        ->name('staff.')
        ->group(function (): void {
            Volt::route('bookings', 'pages.staff.bookings')
                ->name('bookings');
        });

    Route::prefix('admin')
        ->middleware('role:admin')
        ->name('admin.')
        ->group(function (): void {
            Volt::route('items', 'pages.admin.items')
                ->name('items');

            Volt::route('reports', 'pages.admin.reports')
                ->name('reports');
        });
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
