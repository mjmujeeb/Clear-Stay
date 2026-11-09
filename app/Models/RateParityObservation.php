<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateParityObservation extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'channel_id',
        'date',
        'expected_rate',
        'observed_rate',
        'difference_amount',
        'is_discrepancy',
        'observed_at',
        'details',
    ];

    protected $casts = [
        'date' => 'date',
        'expected_rate' => 'float',
        'observed_rate' => 'float',
        'difference_amount' => 'float',
        'is_discrepancy' => 'boolean',
        'observed_at' => 'datetime',
        'details' => 'array',
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
