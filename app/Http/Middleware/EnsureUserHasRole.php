<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Superadmin bypasses role restrictions
        if ($user->role === 'superadmin') {
            return $next($request);
        }

        if (!in_array($user->role, $roles)) {
            abort(403, 'Unauthorized access: insufficient role permissions.');
        }

        return $next($request);
    }
}
