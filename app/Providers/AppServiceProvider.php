<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('api', function (Request $request): Limit {
            $identificador = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(60)->by((string) $identificador);
        });

        RateLimiter::for('auth', function (Request $request): Limit {
            $email = (string) $request->input('email', 'guest');

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request): Limit {
            $email = (string) $request->input('email', 'guest');

            return Limit::perMinute(3)->by($email.'|'.$request->ip());
        });
    }
}
