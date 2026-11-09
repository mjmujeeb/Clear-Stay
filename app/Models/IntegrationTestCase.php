<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationTestCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_suite_id',
        'case_code',
        'name',
        'description',
        'request_payload',
        'expected_status_code',
        'expected_assertions',
        'is_simulation_safe',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'expected_assertions' => 'array',
        'expected_status_code' => 'integer',
        'is_simulation_safe' => 'boolean',
    ];

    public function suite(): BelongsTo
    {
        return $this->belongsTo(IntegrationTestSuite::class, 'test_suite_id');
    }
}
