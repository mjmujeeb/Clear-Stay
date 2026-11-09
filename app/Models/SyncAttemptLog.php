<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncAttemptLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_outbox_event_id',
        'attempt_number',
        'http_status',
        'request_payload',
        'response_payload',
        'execution_time_ms',
        'error_message',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'http_status' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'execution_time_ms' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(SyncOutboxEvent::class, 'sync_outbox_event_id');
    }
}
