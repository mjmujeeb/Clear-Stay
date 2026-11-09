<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSnapshot extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'snapshot_date',
        'stay_date',
        'room_type_id',
        'channel_id',
        'booking_count',
        'occupied_rooms',
        'available_rooms',
        'revenue',
        'cancellation_count',
        'adr',
        'revpar',
        'occupancy_rate',
        'metadata',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'stay_date' => 'date',
        'booking_count' => 'integer',
        'occupied_rooms' => 'integer',
        'available_rooms' => 'integer',
        'revenue' => 'decimal:2',
        'cancellation_count' => 'integer',
        'adr' => 'decimal:2',
        'revpar' => 'decimal:2',
        'occupancy_rate' => 'decimal:2',
        'metadata' => 'array',
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
