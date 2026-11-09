<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permission_id',
        'access_type', // allow, deny
        'is_temporary',
        'starts_at',
        'expires_at',
        'granted_by',
        'reason',
    ];

    protected $casts = [
        'is_temporary' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function grantedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Check if this permission override is currently active and unexpired.
     */
    public function isValidNow(): bool
    {
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->is_temporary && $this->expires_at && $now->gte($this->expires_at)) {
            return false;
        }
        return true;
    }
}
