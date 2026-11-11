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
        if (
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https')) ||
            (request()->server('HTTP_X_FORWARDED_PROTO') === 'https') ||
            (request()->header('x-forwarded-proto') === 'https') ||
            (request()->header('cf-visitor') && str_contains(request()->header('cf-visitor'), 'https')) ||
            str_contains(request()->getHost(), 'trycloudflare.com') ||
            app()->environment('production')
        ) {
            URL::forceScheme('https');
        }
    }
}
