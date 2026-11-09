<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationTestRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_suite_id',
        'partner_id',
        'run_by',
        'status',
        'total_cases',
        'passed_cases',
        'failed_cases',
        'duration_ms',
        'results_summary',
    ];

    protected $casts = [
        'total_cases' => 'integer',
        'passed_cases' => 'integer',
        'failed_cases' => 'integer',
        'duration_ms' => 'integer',
        'results_summary' => 'array',
    ];

    public function suite(): BelongsTo
    {
        return $this->belongsTo(IntegrationTestSuite::class, 'test_suite_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(IntegrationPartner::class, 'partner_id');
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by');
    }
}
