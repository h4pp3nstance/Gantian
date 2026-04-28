<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-catalog', fn (User $user): bool => $user->isAdmin());
        Gate::define('view-revenue-reports', fn (User $user): bool => $user->isAdmin());

        Gate::define('validate-bookings', fn (User $user): bool => $user->canActAs(User::ROLE_STAFF));
        Gate::define('process-checkout', fn (User $user): bool => $user->canActAs(User::ROLE_STAFF));
        Gate::define('process-checkin', fn (User $user): bool => $user->canActAs(User::ROLE_STAFF));
        Gate::define('issue-fines', fn (User $user): bool => $user->canActAs(User::ROLE_STAFF));

        Gate::define('submit-bookings', fn (User $user): bool => $user->isCustomer());
    }
}
