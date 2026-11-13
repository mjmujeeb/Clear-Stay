<?php

namespace App\Models;

use App\Casts\NormalizedDateCast;
use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReconciliation extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'channel_id',
        'stay_date',
        'central_units',
        'channel_units',
        'difference',
        'status',
        'last_synced_at',
        'suggested_action',
        'resolved_at',
    ];

    protected $casts = [
        'stay_date' => NormalizedDateCast::class,
        'central_units' => 'integer',
        'channel_units' => 'integer',
        'difference' => 'integer',
        'last_synced_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
