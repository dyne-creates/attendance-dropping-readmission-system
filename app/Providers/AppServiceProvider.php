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
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('authenticated-submissions', function (Request $request): Limit {
            return Limit::perMinute(30)->by(
                $request->user()->getAuthIdentifier().'|'.$request->ip(),
            );
        });

        RateLimiter::for('attendance-updates', function (Request $request): Limit {
            return Limit::perMinute(120)->by(
                $request->user()->getAuthIdentifier().'|'.$request->ip(),
            );
        });

        RateLimiter::for('resource-intensive', function (Request $request): Limit {
            return Limit::perMinute(10)->by(
                $request->user()->getAuthIdentifier().'|'.$request->ip(),
            );
        });
    }
}
