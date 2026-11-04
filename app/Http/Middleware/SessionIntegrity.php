<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\SecurityIncident;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionIntegrity
{
    /**
     * Verify session integrity and client fingerprint to prevent session hijacking.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentFingerprint = hash('sha256', ($request->header('User-Agent') ?? '') . '|' . $this->getClientSubnet($request->ip()));

            if (!$request->session()->has('sec_device_fingerprint')) {
                $request->session()->put('sec_device_fingerprint', $currentFingerprint);
                $request->session()->put('sec_login_ip', $request->ip());
            } else {
                $storedFingerprint = $request->session()->get('sec_device_fingerprint');

                if (!hash_equals($storedFingerprint, $currentFingerprint)) {
                    SecurityIncident::log('security.session_hijack_attempt', 'critical', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'original_ip' => $request->session()->get('sec_login_ip'),
                        'current_ip' => $request->ip(),
                        'user_agent' => $request->header('User-Agent'),
                    ], $request, true);

                    AuditLog::record('security.session_invalidated', $user, null, [
                        'reason' => 'Client device fingerprint or IP subnet changed abruptly.',
                    ]);

                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->with('error', __('clearstay.security.session_hijack_warning'));
                }
            }
        }

        return $next($request);
    }

    /**
     * Get IP subnet prefix to allow minor wireless/cellular IP fluctuations while catching major geo jumps.
     */
    protected function getClientSubnet(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return $parts[0] . '.' . $parts[1] . '.' . ($parts[2] ?? '0');
        }
        return substr($ip, 0, 16);
    }
}
