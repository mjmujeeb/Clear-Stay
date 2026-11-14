<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimulatedPartnerRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'correlation_id',
        'partner_code',
        'direction',
        'endpoint',
        'http_status',
        'duration_ms',
        'request_payload',
        'response_payload',
        'status',
        'error_summary',
    ];

    protected $casts = [
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];
}
