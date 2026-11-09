<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'contract_name',
        'schema_format',
        'spec_content',
        'version',
        'is_active',
        'validation_rules',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'validation_rules' => 'array',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(IntegrationPartner::class, 'partner_id');
    }
}
