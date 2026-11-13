<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkChangeBatch extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'user_id',
        'change_type',
        'summary',
        'scope_criteria',
        'previous_state',
        'new_state',
        'status',
        'rolled_back_at',
        'rolled_back_by',
    ];

    protected $casts = [
        'scope_criteria' => 'array',
        'previous_state' => 'array',
        'new_state' => 'array',
        'rolled_back_at' => 'datetime',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rolledBackByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }

    /**
     * Virtual accessor: true when this batch has been rolled back.
     * Derived from the canonical `status` column so callers can use
     * either `$batch->is_rolled_back` (boolean) or `$batch->status === 'rolled_back'`.
     */
    public function getIsRolledBackAttribute(): bool
    {
        return $this->status === 'rolled_back';
    }
}
