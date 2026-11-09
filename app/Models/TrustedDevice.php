<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_identifier',
        'device_name',
        'browser',
        'os',
        'ip_address',
        'trust_status', // trusted, new, verification_required, revoked
        'first_seen_at',
        'last_seen_at',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isTrusted(): bool
    {
        return $this->trust_status === 'trusted';
    }

    public function isRevoked(): bool
    {
        return $this->trust_status === 'revoked';
    }
}
