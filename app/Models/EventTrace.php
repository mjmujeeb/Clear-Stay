<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTrace extends Model
{
    use HasFactory;

    protected $fillable = [
        'trace_id',
        'partner_id',
        'hotel_id',
        'operation',
        'stage',
        'status',
        'duration_ms',
        'request_headers',
        'request_body',
        'response_headers',
        'response_body',
        'error_details',
        'replayable',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'error_details' => 'array',
        'replayable' => 'boolean',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(IntegrationPartner::class, 'partner_id');
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
}
