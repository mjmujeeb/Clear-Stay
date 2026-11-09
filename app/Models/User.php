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
        'department',
        'phone',
        'locale',
        'is_active',
        'is_suspended',
        'suspension_reason',
        'suspended_until',
        'access_expires_at',
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
            'is_suspended' => 'boolean',
            'suspended_until' => 'datetime',
            'access_expires_at' => 'datetime',
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

    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    // Role checks
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['superadmin', 'manager', 'owner', 'gm']);
    }

    public function isFrontDesk(): bool
    {
        return in_array($this->role, ['superadmin', 'manager', 'frontdesk', 'receptionist']);
    }

    public function isHousekeeping(): bool
    {
        return in_array($this->role, ['superadmin', 'manager', 'housekeeping', 'cleaner']);
    }

    public function isMaintenance(): bool
    {
        return in_array($this->role, ['superadmin', 'manager', 'maintenance']);
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

    /**
     * Check if user account is currently suspended.
     */
    public function isSuspended(): bool
    {
        if (!$this->is_suspended) {
            return false;
        }
        if ($this->suspended_until && now()->gte($this->suspended_until)) {
            // Auto-reactivation after temporary suspension
            $this->is_suspended = false;
            $this->suspended_until = null;
            $this->save();
            return false;
        }
        return true;
    }

    /**
     * Check if user's account access has expired.
     */
    public function isAccessExpired(): bool
    {
        return $this->access_expires_at && now()->gte($this->access_expires_at);
    }

    /**
     * Comprehensive account usable check.
     */
    public function isAccountUsable(): bool
    {
        return $this->is_active && !$this->isSuspended() && !$this->isAccessExpired() && !$this->isLockedOut();
    }

    /**
     * Hybrid RBAC + ABAC permission evaluation for this user.
     * Precedence:
     * 1. Account disabled/suspended -> false
     * 2. Superadmin -> true
     * 3. Explicit Deny override -> false
     * 4. Explicit Allow override -> true
     * 5. Role default permission -> true/false
     */
    public function hasPermission(string $permissionName): bool
    {
        if (!$this->isAccountUsable()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check individual overrides
        $override = $this->userPermissions()
            ->whereHas('permission', fn($q) => $q->where('name', $permissionName))
            ->first();

        if ($override && $override->isValidNow()) {
            if ($override->access_type === 'deny') {
                return false;
            }
            if ($override->access_type === 'allow') {
                return true;
            }
        }

        // Check role-based default permissions
        return $this->hasRoleDefaultPermission($permissionName);
    }

    /**
     * Role-based default permissions matrix.
     */
    protected function hasRoleDefaultPermission(string $perm): bool
    {
        $role = $this->role;

        // Managers have broad hotel management authority
        if (in_array($role, ['manager', 'owner', 'gm'])) {
            $restrictedFromManager = [
                'platform.admin',
                'tenants.manage_global',
                'policies.global',
            ];
            return !in_array($perm, $restrictedFromManager);
        }

        // Front desk / Receptionist
        if (in_array($role, ['frontdesk', 'receptionist'])) {
            return in_array($perm, [
                'reservations.view',
                'reservations.create',
                'reservations.modify',
                'reservations.check_in',
                'reservations.check_out',
                'rooms.view_board',
                'guest.view_portal',
                'guest.request_amenities',
            ]);
        }

        // Housekeeping / Cleaner
        if (in_array($role, ['housekeeping', 'cleaner'])) {
            return in_array($perm, [
                'housekeeping.view_assigned',
                'housekeeping.update_status',
                'rooms.view_board',
            ]);
        }

        // Maintenance
        if ($role === 'maintenance') {
            return in_array($perm, [
                'maintenance.view_tickets',
                'maintenance.update_status',
                'rooms.view_board',
            ]);
        }

        // Revenue Manager
        if ($role === 'revenue_manager') {
            return in_array($perm, [
                'analytics.view',
                'rates.view',
                'rates.edit',
                'rates.bulk_edit',
                'channels.view',
                'inventory.view',
                'inventory.edit',
                'reservations.view',
            ]);
        }

        // Integration Engineer / QA
        if (in_array($role, ['integration_engineer', 'qa_engineer'])) {
            return in_array($perm, [
                'partners.view',
                'partners.test',
                'contracts.validate',
                'simulation.run',
                'event_trace.view',
                'readiness.view',
            ]);
        }

        return false;
    }
}
