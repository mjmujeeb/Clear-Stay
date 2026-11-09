<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelConnection extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'channel_id',
        'is_enabled',
        'credentials',
        'sync_interval_mins',
        'last_successful_sync',
        'sync_status',
        'error_count',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'credentials' => 'array',
        'last_successful_sync' => 'datetime',
        'error_count' => 'integer',
        'sync_interval_mins' => 'integer',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function roomMappings(): HasMany
    {
        return $this->hasMany(ChannelRoomMapping::class);
    }

    public function channelRoomMappings(): HasMany
    {
        return $this->roomMappings();
    }

    public function outboxEvents(): HasMany
    {
        return $this->hasMany(SyncOutboxEvent::class);
    }
}
