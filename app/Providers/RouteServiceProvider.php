<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('contact-submit', function (Request $request) {
            $perMinute = (int) config('antispam.rate_per_minute', 1);
            $key = $request->ip().'|'.$request->input('email');
            return [Limit::perMinute($perMinute)->by($key)];
        });

        $this->routes(function () {
            // default routes are loaded by Laravel
        });
    }
}

