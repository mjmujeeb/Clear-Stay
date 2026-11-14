<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelPricingSetting extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'stacking_policy',
        'weekend_days',
        'weekend_increase_percentage',
        'rounding_policy',
        'currency',
        'tax_percentage',
        'tax_included',
    ];

    protected $casts = [
        'weekend_days' => 'array',
        'weekend_increase_percentage' => 'float',
        'tax_percentage' => 'float',
        'tax_included' => 'boolean',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
