<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityIncident;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpFirewall
{
    /**
     * Handle an incoming request and verify against IP blacklist.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if (BlockedIp::isBlocked($ip)) {
            SecurityIncident::log('firewall.blocked_ip_rejected', 'critical', [
                'ip' => $ip,
                'path' => $request->path(),
            ], $request, true);

            return response()->view('errors.firewall-blocked', [
                'ip' => $ip,
            ], 403);
        }

        return $next($request);
    }
}
