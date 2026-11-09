<?php

namespace App\Services\Governance;

use App\Models\AuditLog;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceTrustService
{
    /**
     * Compute a deterministic client device fingerprint.
     */
    public function computeFingerprint(Request $request): string
    {
        return hash('sha256', ($request->userAgent() ?? 'agent') . '|' . ($request->ip() ?? '127.0.0.1'));
    }

    /**
     * Check if a device fingerprint is trusted for the user.
     */
    public function isDeviceTrusted(User $user, string $fingerprint): bool
    {
        return TrustedDevice::where('user_id', $user->id)
            ->where('device_identifier', $fingerprint)
            ->where('trust_status', 'trusted')
            ->exists();
    }

    /**
     * Register and trust a device for a user.
     */
    public function registerDevice(User $user, Request $request, string $name): TrustedDevice
    {
        $fingerprint = $this->computeFingerprint($request);
        $userAgent = $request->userAgent() ?? 'Unknown Browser';
        $ip = $request->ip();

        $device = TrustedDevice::updateOrCreate(
            ['user_id' => $user->id, 'device_identifier' => $fingerprint],
            [
                'device_name' => $name,
                'browser' => $this->parseBrowser($userAgent),
                'os' => $this->parseOs($userAgent),
                'ip_address' => $ip,
                'trust_status' => 'trusted',
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'revoked_at' => null,
            ]
        );

        AuditLog::record('device.trusted', $user, null, [
            'device_name' => $device->device_name,
            'ip' => $ip,
        ]);

        return $device;
    }

    /**
     * Evaluate incoming device request for an authenticated user.
     */
    public function evaluateDevice(User $user, Request $request): array
    {
        $userAgent = $request->userAgent() ?? 'Unknown Browser';
        $ip = $request->ip();
        $deviceIdentifier = $this->computeFingerprint($request);

        $device = TrustedDevice::where('user_id', $user->id)
            ->where('device_identifier', $deviceIdentifier)
            ->first();

        if (!$device) {
            $device = TrustedDevice::create([
                'user_id' => $user->id,
                'device_identifier' => $deviceIdentifier,
                'device_name' => $this->parseDeviceName($userAgent),
                'browser' => $this->parseBrowser($userAgent),
                'os' => $this->parseOs($userAgent),
                'ip_address' => $ip,
                'trust_status' => 'new',
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);

            AuditLog::record('device.new_detected', $user, null, [
                'device_name' => $device->device_name,
                'ip' => $ip,
            ]);

            return [
                'device_id' => $device->id,
                'status' => 'new',
                'message' => 'New unrecognized device detected.',
            ];
        }

        $device->update(['last_seen_at' => now(), 'ip_address' => $ip]);

        return [
            'device_id' => $device->id,
            'status' => $device->trust_status,
            'message' => "Device evaluated with status {$device->trust_status}.",
        ];
    }

    /**
     * Mark a device as trusted.
     */
    public function trustDevice(int|TrustedDevice $device): bool
    {
        $deviceModel = is_int($device) ? TrustedDevice::findOrFail($device) : $device;
        $deviceModel->update(['trust_status' => 'trusted', 'revoked_at' => null]);

        AuditLog::record('device.trusted', $deviceModel->user, null, ['device_name' => $deviceModel->device_name]);
        return true;
    }

    /**
     * Revoke trust on a device.
     */
    public function revokeDevice(int|TrustedDevice $device, ?string $reason = null, ?int $revokedBy = null): bool
    {
        $deviceModel = is_int($device) ? TrustedDevice::findOrFail($device) : $device;
        $deviceModel->update([
            'trust_status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $revokedBy ?? Auth::id(),
        ]);

        AuditLog::record('device.revoked', $deviceModel->user, null, [
            'device_name' => $deviceModel->device_name,
            'reason' => $reason,
            'revoked_by' => $revokedBy ?? Auth::id(),
        ]);

        return true;
    }

    public function parseDeviceName(string $ua): string
    {
        if (str_contains($ua, 'Mobile') || str_contains($ua, 'Android') || str_contains($ua, 'iPhone')) {
            return 'Mobile Handset';
        }
        if (str_contains($ua, 'iPad') || str_contains($ua, 'Tablet')) {
            return 'Tablet Terminal';
        }
        return 'Desktop Workstation';
    }

    public function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Chrome')) return 'Chrome';
        if (str_contains($ua, 'Safari')) return 'Safari';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Edge')) return 'Edge';
        return 'Standard Browser';
    }

    public function parseOs(string $ua): string
    {
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Mac')) return 'macOS';
        if (str_contains($ua, 'Linux')) return 'Linux';
        if (str_contains($ua, 'iOS')) return 'iOS';
        if (str_contains($ua, 'Android')) return 'Android';
        return 'Operating System';
    }
}
