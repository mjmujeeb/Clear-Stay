<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncOutboxEvent extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'channel_connection_id',
        'correlation_id',
        'event_type',
        'payload',
        'status',
        'attempts',
        'last_error',
        'scheduled_at',
        'executed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function channelConnection(): BelongsTo
    {
        return $this->belongsTo(ChannelConnection::class);
    }

    public function attemptLogs(): HasMany
    {
        return $this->hasMany(SyncAttemptLog::class);
    }
}
