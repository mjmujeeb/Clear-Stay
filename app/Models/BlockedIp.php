<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'reason',
        'threat_level',
        'violation_count',
        'expires_at',
    ];

    protected $casts = [
        'violation_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * Check if an IP address is actively blocked by the firewall.
     */
    public static function isBlocked(string $ip): bool
    {
        try {
            $record = self::where('ip_address', $ip)->first();

            if (!$record) {
                return false;
            }

            // If expires_at is in the past, unblock
            if ($record->expires_at && $record->expires_at->isPast()) {
                $record->delete();
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            // Fail open if database is temporarily unavailable or during schema initialization
            return false;
        }
    }

    /**
     * Block or increment violation count for an IP.
     */
    public static function block(string $ip, string $reason, string $threatLevel = 'high', ?int $durationMinutes = 60): self
    {
        $record = self::where('ip_address', $ip)->first();

        $expiresAt = $durationMinutes ? Carbon::now()->addMinutes($durationMinutes) : null;

        if ($record) {
            $record->increment('violation_count');
            $record->update([
                'reason' => $reason,
                'threat_level' => $threatLevel,
                'expires_at' => $expiresAt,
            ]);
            return $record;
        }

        return self::create([
            'ip_address' => $ip,
            'reason' => $reason,
            'threat_level' => $threatLevel,
            'violation_count' => 1,
            'expires_at' => $expiresAt,
        ]);
    }
}
