<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'hotel_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'locale',
        'is_active',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'lockout_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_recovery_codes' => 'array',
            'last_login_at' => 'datetime',
            'lockout_until' => 'datetime',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(HousekeepingTask::class, 'assigned_to');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // Role checks
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['superadmin', 'manager']);
    }

    public function isFrontDesk(): bool
    {
        return in_array($this->role, ['superadmin', 'manager', 'frontdesk']);
    }

    public function isHousekeeping(): bool
    {
        return in_array($this->role, ['superadmin', 'manager', 'housekeeping']);
    }

    public function canManageHotel(?int $hotelId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->hotel_id === $hotelId;
    }

    /**
     * Check if user account is currently locked due to too many failed attempts.
     */
    public function isLockedOut(): bool
    {
        return $this->lockout_until && $this->lockout_until->isFuture();
    }
}
