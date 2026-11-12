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

                $rawUrl = (string) (env('DATABASE_URL') ?: env('DB_URL') ?: env('POSTGRES_URL') ?: '');
                $cleanUrl = trim($rawUrl, "\"' \t\n\r");
                $parsed = parse_url($cleanUrl);
                $urlHost = $parsed['host'] ?? null;
                $urlScheme = $parsed['scheme'] ?? null;

                // 1. Encryption subsystem test
                $encryptionTest = 'unknown';
                try {
                    $plain = 'clearstay_probe_test';
                    $enc = \Illuminate\Support\Facades\Crypt::encryptString($plain);
                    $dec = \Illuminate\Support\Facades\Crypt::decryptString($enc);
                    $encryptionTest = ($dec === $plain) ? 'working' : 'mismatch';
                } catch (\Throwable $e) {
                    $encryptionTest = 'failed: ' . $e->getMessage();
                }

                // 2. Storage permissions test
                $storageTest = [];
                foreach (['sessions' => 'framework/sessions', 'views' => 'framework/views', 'logs' => 'logs'] as $key => $subDir) {
                    try {
                        $targetDir = storage_path($subDir);
                        if (!is_dir($targetDir)) {
                            @mkdir($targetDir, 0775, true);
                        }
                        $testPath = $targetDir . '/probe_' . uniqid() . '.tmp';
                        file_put_contents($testPath, 'probe');
                        unlink($testPath);
                        $storageTest[$key] = 'writable';
                    } catch (\Throwable $e) {
                        $storageTest[$key] = 'error: ' . $e->getMessage();
                    }
                }

                // 3. Controller execution test
                $homeTest = 'unknown';
                try {
                    $ctrl = app(\App\Http\Controllers\PublicController::class);
                    $view = $ctrl->home();
                    $content = $view->render();
                    $homeTest = 'ok (rendered ' . strlen($content) . ' bytes)';
                } catch (\Throwable $e) {
                    $homeTest = 'failed: ' . get_class($e) . ': ' . $e->getMessage() . ' at ' . basename($e->getFile()) . ':' . $e->getLine();
                }

                // 4. HTTP pipeline test for GET /
                $pipelineTest = 'unknown';
                try {
                    $req = \Illuminate\Http\Request::create('/', 'GET');
                    $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
                    $res = $kernel->handle($req);
                    $pipelineTest = 'HTTP ' . $res->getStatusCode();
                } catch (\Throwable $e) {
                    $pipelineTest = 'exception: ' . get_class($e) . ': ' . $e->getMessage() . ' at ' . basename($e->getFile()) . ':' . $e->getLine();
                }

                return response()->json([
                    'status' => $dbStatus === 'connected' ? 'ok' : 'degraded',
                    'commit' => 'e7a9b1c-diag',
                    'app_env' => config('app.env'),
                    'app_debug' => config('app.debug'),
                    'has_app_key' => !empty(config('app.key')),
                    'encryption' => $encryptionTest,
                    'storage_writable' => $storageTest,
                    'home_controller' => $homeTest,
                    'pipeline_home' => $pipelineTest,
                    'database' => [
                        'driver' => $dbDriver,
                        'host' => $dbHost,
                        'resolved_url_host' => $urlHost,
                        'url_scheme' => $urlScheme,
                        'has_DATABASE_URL' => !empty(env('DATABASE_URL')),
                        'has_POSTGRES_URL' => !empty(env('POSTGRES_URL')),
                        'has_DB_HOST' => !empty(env('DB_HOST')),
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

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->has('diag') || $request->header('X-ClearStay-Diag') === '1') {
                return response()->json([
                    'status' => 'error',
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'trace' => collect($e->getTrace())->take(8)->map(fn ($t) => [
                        'file' => ($t['file'] ?? 'internal') . ':' . ($t['line'] ?? 0),
                        'call' => ($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? ''),
                    ])->toArray(),
                ], 500);
            }
        });
    })->create();
