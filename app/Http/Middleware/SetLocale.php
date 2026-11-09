<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check query parameter override (?locale=en or ?lang=ja)
        $queryLocale = $request->query('locale') ?? $request->query('lang');
        if ($queryLocale && in_array($queryLocale, ['en', 'ja'])) {
            session(['locale' => $queryLocale]);
            if (auth()->check()) {
                $user = auth()->user();
                $user->locale = $queryLocale;
                $user->save();
            }
            App::setLocale($queryLocale);
            return $next($request);
        }

        // 2. Check session
        $locale = session('locale');

        // 3. Check authenticated user preference
        if (!$locale && auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
        }

        // 4. Fallback to default
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }

        if (in_array($locale, ['en', 'ja'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
