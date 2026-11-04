<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SecurityIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'severity',
        'ip_address',
        'request_method',
        'endpoint',
        'user_agent',
        'details',
        'is_blocked',
    ];

    protected $casts = [
        'details' => 'array',
        'is_blocked' => 'boolean',
    ];

    /**
     * Record a cyber threat or security verification incident.
     */
    public static function log(
        string $eventType,
        string $severity = 'medium',
        ?array $details = null,
        ?Request $request = null,
        bool $isBlocked = false
    ): self {
        $req = $request ?? request();

        return self::create([
            'event_type' => $eventType,
            'severity' => $severity,
            'ip_address' => $req ? $req->ip() : '127.0.0.1',
            'request_method' => $req ? $req->method() : 'CLI',
            'endpoint' => $req ? substr($req->fullUrl(), 0, 500) : null,
            'user_agent' => $req ? substr($req->userAgent() ?? '', 0, 500) : null,
            'details' => $details,
            'is_blocked' => $isBlocked,
        ]);
    }
}
