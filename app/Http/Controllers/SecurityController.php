<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\Hotel;
use App\Models\SecurityIncident;
use App\Services\TwoFactorAuthService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityController extends Controller
{
    protected TwoFactorAuthService $twoFactorService;

    public function __construct(TwoFactorAuthService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Cyber Threat Intelligence & Firewall Center.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId);

        $severityFilter = $request->input('severity');
        $eventTypeFilter = $request->input('event_type');

        $incidentQuery = SecurityIncident::orderBy('created_at', 'desc');

        if ($severityFilter) {
            $incidentQuery->where('severity', $severityFilter);
        }

        if ($eventTypeFilter) {
            $incidentQuery->where('event_type', $eventTypeFilter);
        }

        $incidents = $incidentQuery->paginate(15)->withQueryString();

        // Cybersecurity Threat Metrics
        $totalIncidents = SecurityIncident::count();
        $blockedBots = SecurityIncident::where('event_type', 'like', 'bot.%')->count();
        $honeypotHits = SecurityIncident::where('event_type', 'bot.honeypot_triggered')->count();
        $bruteForceBlocks = SecurityIncident::whereIn('event_type', ['auth.rate_limited', 'auth.failed', 'bot.scanner_blocked'])->count();
        $activeIpBlocks = BlockedIp::count();

        // Determine Threat Level
        $criticalRecent = SecurityIncident::where('created_at', '>=', now()->subHours(24))
            ->whereIn('severity', ['high', 'critical'])
            ->count();

        if ($criticalRecent > 10) {
            $threatLevel = 'CRITICAL';
            $threatBadge = 'danger';
        } elseif ($criticalRecent > 3) {
            $threatLevel = 'ELEVATED';
            $threatBadge = 'warning';
        } else {
            $threatLevel = 'NORMAL / SHIELDED';
            $threatBadge = 'success';
        }

        $blockedIps = BlockedIp::orderBy('created_at', 'desc')->get();

        // 2FA User Status
        $user = Auth::user();

        return view('security.index', compact(
            'hotel',
            'incidents',
            'totalIncidents',
            'blockedBots',
            'honeypotHits',
            'bruteForceBlocks',
            'activeIpBlocks',
            'threatLevel',
            'threatBadge',
            'blockedIps',
            'user',
            'severityFilter',
            'eventTypeFilter'
        ));
    }

    /**
     * Manually ban an IP in the firewall.
     */
    public function blockIp(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|string|max:45',
            'reason' => 'required|string|max:255',
            'threat_level' => 'required|in:medium,high,critical',
            'duration' => 'required|integer|min:1',
        ]);

        BlockedIp::block(
            $request->ip_address,
            $request->reason,
            $request->threat_level,
            intval($request->duration)
        );

        AuditLog::record('firewall.manual_ip_block', null, null, [
            'ip' => $request->ip_address,
            'reason' => $request->reason,
            'duration_minutes' => $request->duration,
        ]);

        return back()->with('success', __('clearstay.security.ip_blocked_success', ['ip' => $request->ip_address]));
    }

    /**
     * Unban an IP from the firewall.
     */
    public function unblockIp(int $id)
    {
        $record = BlockedIp::findOrFail($id);
        $ip = $record->ip_address;
        $record->delete();

        AuditLog::record('firewall.manual_ip_unblock', null, null, ['ip' => $ip]);

        return back()->with('success', __('clearstay.security.ip_unblocked_success', ['ip' => $ip]));
    }

    /**
     * Enable / Setup 2FA for current staff account.
     */
    public function setup2fa(Request $request)
    {
        $user = Auth::user();

        if ($user->two_factor_enabled) {
            return back()->with('info', __('clearstay.security.two_factor_already_active'));
        }

        $secretPin = $this->twoFactorService->generateSecurityPin();
        $recoveryCodes = $this->twoFactorService->enable($user, $secretPin);

        return back()->with('success_2fa', [
            'pin' => $secretPin,
            'codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disable 2FA for current staff account.
     */
    public function disable2fa(Request $request)
    {
        $user = Auth::user();
        $this->twoFactorService->disable($user);

        return back()->with('info', __('clearstay.security.two_factor_disabled'));
    }
}
