<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\IpFirewall;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SessionIntegrity;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            \Illuminate\Support\Facades\Route::get('/up/status', function () {
                $dbStatus = 'unknown';
                $dbError = null;
                $dbDriver = config('database.default');
                $dbHost = config("database.connections.{$dbDriver}.host") ?: 'N/A';
                $hasUrl = !empty(env('DATABASE_URL')) || !empty(env('DB_URL')) || !empty(env('POSTGRES_URL'));

                try {
                    \Illuminate\Support\Facades\DB::connection()->getPdo();
                    $dbStatus = 'connected';
                } catch (\Throwable $e) {
                    $dbStatus = 'failed';
                    $dbError = preg_replace('/:[^:@\s]+@/', ':****@', $e->getMessage());
                    $dbError = preg_replace('/password=[^\s;]+/', 'password=****', (string) $dbError);
                }

                $tables = [];
                if ($dbStatus === 'connected') {
                    try {
                        $tables = [
                            'users' => \Illuminate\Support\Facades\Schema::hasTable('users'),
                            'hotels' => \Illuminate\Support\Facades\Schema::hasTable('hotels'),
                            'sessions' => \Illuminate\Support\Facades\Schema::hasTable('sessions'),
                            'cache' => \Illuminate\Support\Facades\Schema::hasTable('cache'),
                            'blocked_ips' => \Illuminate\Support\Facades\Schema::hasTable('blocked_ips'),
                        ];
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                return response()->json([
                    'status' => $dbStatus === 'connected' ? 'ok' : 'degraded',
                    'app_env' => config('app.env'),
                    'app_debug' => config('app.debug'),
                    'has_app_key' => !empty(config('app.key')),
                    'database' => [
                        'driver' => $dbDriver,
                        'host' => $dbHost,
                        'has_url' => $hasUrl,
                        'status' => $dbStatus,
                        'error' => $dbError,
                        'tables' => $tables,
                    ],
                    'session' => [
                        'driver' => config('session.driver'),
                    ],
                    'cache' => [
                        'driver' => config('cache.default'),
                    ],
                ]);
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(prepend: [
            IpFirewall::class,
            SecurityHeaders::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            SessionIntegrity::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'firewall' => IpFirewall::class,
            'security.headers' => SecurityHeaders::class,
            'session.integrity' => SessionIntegrity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
