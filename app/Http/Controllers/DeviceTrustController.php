<?php

namespace App\Http\Controllers;

use App\Models\AuthorizationDecision;
use App\Models\TrustedDevice;
use App\Services\Governance\DeviceTrustService;
use App\Services\Governance\HybridAccessControlService;
use Illuminate\Http\Request;

class DeviceTrustController extends Controller
{
    public function __construct(
        protected DeviceTrustService $deviceService,
        protected HybridAccessControlService $accessControl
    ) {}

    public function index(Request $request)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        $decision = $this->accessControl->evaluate($currentUser, 'governance.staff_view', [
            'hotel_id' => $hotelId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($decision->isDenied()) {
            return redirect()->route('dashboard')->with('error', 'Access denied: ' . $decision->getReason());
        }

        $query = TrustedDevice::with(['user']);
        if ($currentUser->role !== 'owner') {
            $query->whereHas('user', function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId);
            });
        }

        $devices = $query->latest('last_seen_at')->paginate(20);

        // Current device fingerprint
        $currentFingerprint = $this->deviceService->computeFingerprint($request);
        $currentIsTrusted = $this->deviceService->isDeviceTrusted($currentUser, $currentFingerprint);

        return view('device_trust.index', compact('devices', 'currentFingerprint', 'currentIsTrusted'));
    }

    public function registerCurrent(Request $request)
    {
        $currentUser = $request->user();

        $request->validate([
            'device_name' => 'required|string|max:100',
        ]);

        $device = $this->deviceService->registerDevice($currentUser, $request, $request->input('device_name'));

        AuthorizationDecision::create([
            'user_id' => $currentUser->id,
            'hotel_id' => $currentUser->hotel_id,
            'action' => 'device.trust_registered',
            'decision' => 'allow',
            'explanation' => "Current device '{$device->device_name}' marked as trusted by user.",
        ]);

        return back()->with('success', "Device '{$device->device_name}' is now registered as a trusted device.");
    }

    public function revoke(Request $request, TrustedDevice $device)
    {
        $currentUser = $request->user();

        if ($currentUser->role !== 'owner' && $device->user->hotel_id !== $currentUser->hotel_id && $device->user_id !== $currentUser->id) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);

        $this->deviceService->revokeDevice($device, $request->input('reason'));

        AuthorizationDecision::create([
            'user_id' => $device->user_id,
            'hotel_id' => $currentUser->hotel_id,
            'action' => 'device.trust_revoked',
            'decision' => 'deny',
            'explanation' => "Device trust revoked by {$currentUser->name}. Rationale: {$request->input('reason')}",
        ]);

        return back()->with('success', "Device '{$device->device_name}' trust has been revoked.");
    }
}
