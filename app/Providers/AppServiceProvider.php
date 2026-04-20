<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        if (!app()->runningInConsole() && app()->environment('production')) {
            $forwardedProto = request()->header('x-forwarded-proto');
            $shouldForceHttps = $forwardedProto === 'https' || env('FORCE_HTTPS', true);

            if ($shouldForceHttps) {
                URL::forceScheme('https');
            }
        }
    }
}
