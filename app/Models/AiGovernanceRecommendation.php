<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGovernanceRecommendation extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'user_id',
        'category',
        'title',
        'recommendation_text',
        'severity',
        'status',
        'metadata',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'reviewed_at' => 'datetime',
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

    /**
     * Confidence score accessor (e.g. 0.88 - 0.95).
     */
    public function getConfidenceScoreAttribute(): float
    {
        return (float) ($this->metadata['confidence_score'] ?? 0.88);
    }

    /**
     * Associated risk score (0-100).
     */
    public function getRiskScoreAttribute(): int
    {
        return (int) ($this->metadata['risk_score'] ?? 20);
    }

    /**
     * Suggested actionable steps.
     */
    public function getSuggestedActionsAttribute(): array
    {
        return (array) ($this->metadata['suggested_actions'] ?? [
            'Review user role assignments in Staff Directory',
            'Verify device certificate status in Device Trust Management'
        ]);
    }
}
