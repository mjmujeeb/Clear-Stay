<?php

namespace App\Models;

use App\Casts\NormalizedDateCast;
use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CentralInventory extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'date',
        'total_units',
        'available_units',
        'reserved_units',
        'maintenance_units',
        'base_rate',
        'currency',
        'min_stay',
        'max_stay',
        'closed_to_arrival',
        'closed_to_departure',
        'stop_sell',
        'version',
    ];

    protected $casts = [
        'date' => NormalizedDateCast::class,
        'total_units' => 'integer',
        'available_units' => 'integer',
        'reserved_units' => 'integer',
        'maintenance_units' => 'integer',
        'base_rate' => 'float',
        'min_stay' => 'integer',
        'max_stay' => 'integer',
        'closed_to_arrival' => 'boolean',
        'closed_to_departure' => 'boolean',
        'stop_sell' => 'boolean',
        'version' => 'integer',
    ];

    /**
     * Compute authoritative availability based on business rules.
     */
    public function calculateAvailableUnits(): int
    {
        return max(0, $this->total_units - $this->reserved_units - $this->maintenance_units);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
