<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerReadinessReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'report_code',
        'evaluated_by',
        'overall_status',
        'score_percentage',
        'summary',
        'category_breakdown',
        'outstanding_issues',
        'evidence_data',
    ];

    protected $casts = [
        'score_percentage' => 'integer',
        'category_breakdown' => 'array',
        'outstanding_issues' => 'array',
        'evidence_data' => 'array',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(IntegrationPartner::class, 'partner_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }
}
