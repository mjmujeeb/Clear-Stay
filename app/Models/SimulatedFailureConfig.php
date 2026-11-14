<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulatedFailureConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_code',
        'operation',
        'failure_type',
        'http_status',
        'latency_ms',
        'error_code',
        'error_message',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'http_status' => 'integer',
        'latency_ms' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope to check if an active failure rule exists for a given partner and operation.
     */
    public static function getActiveFailure(string $partnerCode, string $operation = 'all'): ?self
    {
        return self::where('is_active', true)
            ->where(function ($q) use ($partnerCode) {
                $q->where('partner_code', $partnerCode)->orWhere('partner_code', 'all');
            })
            ->where(function ($q) use ($operation) {
                $q->where('operation', $operation)->orWhere('operation', 'all');
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }
}
