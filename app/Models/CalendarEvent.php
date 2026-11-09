<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_ja',
        'event_type',
        'start_date',
        'end_date',
        'demand_multiplier',
        'recommended_min_stay',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'demand_multiplier' => 'float',
        'recommended_min_stay' => 'integer',
    ];
}
