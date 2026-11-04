<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and attach cybersecurity headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Don't modify streaming responses
        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-XSS-Protection', '1; mode=block');
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

            // Content-Security-Policy accommodating CDN resources
            $csp = "default-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com https://fonts.gstatic.com https://cdnjs.cloudflare.com https://code.jquery.com https://cdn.jsdelivr.net; img-src 'self' data: https:;";
            $response->header('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
