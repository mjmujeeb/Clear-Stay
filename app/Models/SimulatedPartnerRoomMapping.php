<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulatedPartnerRoomMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_code',
        'property_id',
        'room_type_id',
        'remote_room_code',
        'remote_room_name',
        'remote_rate_plan_code',
        'max_occupancy',
        'base_rate',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_occupancy' => 'integer',
        'base_rate' => 'float',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(SimulatedPartnerProperty::class, 'property_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
