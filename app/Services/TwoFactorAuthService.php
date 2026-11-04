<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SecurityIncident;
use App\Models\User;
use Illuminate\Support\Str;

class TwoFactorAuthService
{
    /**
     * Generate 8 emergency one-time recovery codes.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }
        return $codes;
    }

    /**
     * Generate a 6-digit Security PIN for verification.
     */
    public function generateSecurityPin(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a 2FA code or recovery code for a user.
     */
    public function verify(User $user, string $code): bool
    {
        $code = trim($code);

        // 1. Direct Secret PIN match (if stored as pin or TOTP seed)
        if ($user->two_factor_secret && hash_equals($user->two_factor_secret, $code)) {
            SecurityIncident::log('auth.2fa_success', 'low', ['user_id' => $user->id]);
            return true;
        }

        // 2. Fallback to standard TOTP window (current, -30s, +30s) if base32 secret is used
        if ($user->two_factor_secret && strlen($user->two_factor_secret) >= 16) {
            if ($this->verifyTotp($user->two_factor_secret, $code)) {
                SecurityIncident::log('auth.2fa_success', 'low', ['user_id' => $user->id]);
                return true;
            }
        }

        // 3. Recovery Codes Check
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        foreach ($recoveryCodes as $index => $storedCode) {
            if (hash_equals(strtoupper($storedCode), strtoupper($code))) {
                // Consume recovery code
                unset($recoveryCodes[$index]);
                $user->two_factor_recovery_codes = array_values($recoveryCodes);
                $user->save();

                AuditLog::record('auth.2fa_recovery_code_used', $user, null, ['remaining_codes' => count($recoveryCodes)]);
                SecurityIncident::log('auth.2fa_recovery_code_used', 'medium', ['user_id' => $user->id]);
                return true;
            }
        }

        SecurityIncident::log('auth.2fa_failed', 'high', [
            'user_id' => $user->id,
            'attempted_code' => substr($code, 0, 2) . '****'
        ]);

        return false;
    }

    /**
     * Simple TOTP verification (RFC 6238 compliant timestamp calculation).
     */
    protected function verifyTotp(string $secret, string $code): bool
    {
        $timeSlice = floor(time() / 30);

        for ($offset = -1; $offset <= 1; $offset++) {
            $calcCode = $this->calculateTotp($secret, $timeSlice + $offset);
            if (hash_equals($calcCode, $code)) {
                return true;
            }
        }

        return false;
    }

    protected function calculateTotp(string $secret, int $timeSlice): string
    {
        $timeStr = pack('N*', 0) . pack('N*', $timeSlice);
        $hmac = hash_hmac('sha1', $timeStr, $secret, true);
        $offset = ord(substr($hmac, -1)) & 0x0F;
        $hashPart = substr($hmac, $offset, 4);
        $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;
        $modulo = $value % 1000000;

        return str_pad((string) $modulo, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Enable 2FA for a user.
     */
    public function enable(User $user, string $secret): array
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->two_factor_enabled = true;
        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        AuditLog::record('auth.2fa_enabled', $user);

        return $recoveryCodes;
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user): void
    {
        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        AuditLog::record('auth.2fa_disabled', $user);
    }
}
