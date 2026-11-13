<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAuditRun extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'audit_uuid',
        'hotel_id',
        'initiated_by',
        'started_at',
        'completed_at',
        'duration_ms',
        'selected_channels',
        'total_pairs_checked',
        'matching_pairs',
        'discrepancies',
        'critical_risks',
        'failed_checks',
        'warnings_count',
        'final_status',
        'summary_notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_ms' => 'integer',
        'selected_channels' => 'array',
        'total_pairs_checked' => 'integer',
        'matching_pairs' => 'integer',
        'discrepancies' => 'integer',
        'critical_risks' => 'integer',
        'failed_checks' => 'integer',
        'warnings_count' => 'integer',
        'summary_notes' => 'array',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
