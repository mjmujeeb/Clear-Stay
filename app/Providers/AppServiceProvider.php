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
            !in_array(request()->getHost(), ['127.0.0.1', 'localhost', '::1']) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https')) ||
            (request()->server('HTTP_X_FORWARDED_PROTO') === 'https') ||
            (request()->header('x-forwarded-proto') === 'https') ||
            (request()->header('cf-visitor') && str_contains(request()->header('cf-visitor'), 'https')) ||
            str_contains(request()->getHost(), 'trycloudflare.com') ||
            str_contains(request()->header('host', ''), 'trycloudflare.com') ||
            str_contains(request()->getHost(), 'onrender.com') ||
            app()->environment('production')
        ) {
            URL::forceScheme('https');
            if (isset($this->app['request'])) {
                $this->app['request']->server->set('HTTPS', 'on');
            }
        }
    }
}
