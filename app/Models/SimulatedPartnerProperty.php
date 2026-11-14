<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SimulatedPartnerProperty extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_code',
        'property_code',
        'hotel_id',
        'name',
        'currency',
        'timezone',
        'is_connected',
        'api_version',
        'settings',
    ];

    protected $casts = [
        'is_connected' => 'boolean',
        'settings' => 'array',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomMappings(): HasMany
    {
        return $this->hasMany(SimulatedPartnerRoomMapping::class, 'property_id');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(SimulatedPartnerInventory::class, 'property_id');
    }
}
