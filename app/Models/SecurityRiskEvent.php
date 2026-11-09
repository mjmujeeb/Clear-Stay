<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityRiskEvent extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'user_id',
        'event_type',
        'risk_score',
        'risk_band',
        'contributing_factors',
        'action_taken',
        'is_reviewed',
        'reviewed_by',
        'investigation_notes',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'contributing_factors' => 'array',
        'is_reviewed' => 'boolean',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
