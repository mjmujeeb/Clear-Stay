<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'title',
        'title_ja',
        'event_type',
        'start_date',
        'end_date',
        'demand_multiplier',
        'percentage_adjustment',
        'fixed_surcharge',
        'is_active',
        'recommended_min_stay',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'demand_multiplier' => 'float',
        'percentage_adjustment' => 'float',
        'fixed_surcharge' => 'decimal:2',
        'is_active' => 'boolean',
        'recommended_min_stay' => 'integer',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
