<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorizationDecision extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'decision', // ALLOW, DENY, REQUIRE_MFA, REQUIRE_MANAGER_APPROVAL, RESTRICTED_PENDING_REVIEW
        'explanation',
        'evaluated_context',
    ];

    protected $casts = [
        'evaluated_context' => 'array',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
