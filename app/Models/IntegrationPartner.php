<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'category',
        'description',
        'api_base_url',
        'api_version',
        'auth_type',
        'supported_capabilities',
        'status',
        'contact_email',
    ];

    protected $casts = [
        'supported_capabilities' => 'array',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(PartnerContract::class, 'partner_id');
    }

    public function testSuites(): HasMany
    {
        return $this->hasMany(IntegrationTestSuite::class, 'partner_id');
    }

    public function eventTraces(): HasMany
    {
        return $this->hasMany(EventTrace::class, 'partner_id');
    }

    public function readinessReports(): HasMany
    {
        return $this->hasMany(PartnerReadinessReport::class, 'partner_id');
    }
}
