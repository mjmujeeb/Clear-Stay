<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelRoomMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_connection_id',
        'room_type_id',
        'channel_room_code',
        'channel_rate_plan_code',
        'rate_multiplier',
        'is_active',
    ];

    protected $casts = [
        'rate_multiplier' => 'float',
        'is_active' => 'boolean',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(ChannelConnection::class, 'channel_connection_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
