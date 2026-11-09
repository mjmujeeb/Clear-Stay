<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationTestSuite extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'suite_name',
        'category',
        'description',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(IntegrationPartner::class, 'partner_id');
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(IntegrationTestCase::class, 'test_suite_id');
    }

    public function testRuns(): HasMany
    {
        return $this->hasMany(IntegrationTestRun::class, 'test_suite_id');
    }
}
