<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'user_id',
        'guest_id',
        'action',
        'field_accessed',
        'justification',
        'ip_address',
        'user_agent',
        'correlation_id',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
