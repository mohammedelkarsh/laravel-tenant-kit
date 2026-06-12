<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

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
        Cashier::useCustomerModel(Tenant::class);

        RateLimiter::for('api-auth', function (Request $request): Limit {
            return Limit::perMinutes(
                config('api.rate_limit.auth_decay_minutes', 1),
                config('api.rate_limit.auth_attempts', 5),
            )->by($request->ip());
        });
    }
}
