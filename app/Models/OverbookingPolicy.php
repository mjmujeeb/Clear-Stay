<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OverbookingPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'is_enabled',
        'strategy',
        'max_buffer_rooms',
        'max_buffer_percentage',
        'default_adr',
        'relocation_cost_per_walk',
        'historical_cancellation_rate',
        'historical_noshow_rate',
        'max_tolerated_walk_risk',
        'authorized_by',
        'authorized_at',
        'policy_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'max_buffer_rooms' => 'integer',
            'max_buffer_percentage' => 'float',
            'default_adr' => 'float',
            'relocation_cost_per_walk' => 'float',
            'historical_cancellation_rate' => 'float',
            'historical_noshow_rate' => 'float',
            'max_tolerated_walk_risk' => 'float',
            'authorized_at' => 'datetime',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
