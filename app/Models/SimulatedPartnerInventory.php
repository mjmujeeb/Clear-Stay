<?php

namespace App\Models;

use App\Casts\NormalizedDateCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulatedPartnerInventory extends Model
{
    use HasFactory;

    protected $table = 'simulated_partner_inventory';

    protected $fillable = [
        'partner_code',
        'property_id',
        'remote_room_code',
        'date',
        'available_units',
        'base_rate',
        'stop_sell',
        'min_stay',
        'closed_to_arrival',
        'closed_to_departure',
        'version',
        'last_synced_at',
    ];

    protected $casts = [
        'date' => NormalizedDateCast::class,
        'available_units' => 'integer',
        'base_rate' => 'float',
        'stop_sell' => 'boolean',
        'min_stay' => 'integer',
        'closed_to_arrival' => 'boolean',
        'closed_to_departure' => 'boolean',
        'version' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(SimulatedPartnerProperty::class, 'property_id');
    }
}
