<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to write an audit log entry easily.
     */
    public static function record(string $action, ?Model $auditable = null, ?array $oldValues = null, ?array $newValues = null): self
    {
        $hotelId = session('current_hotel_id');
        $userId = Auth::id();

        if (!$hotelId && Auth::check() && Auth::user()->hotel_id) {
            $hotelId = Auth::user()->hotel_id;
        }

        if (!$hotelId && $auditable && isset($auditable->hotel_id)) {
            $hotelId = $auditable->hotel_id;
        }

        return self::create([
            'hotel_id' => $hotelId,
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable ? $auditable->getKey() : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => substr(Request::userAgent() ?? '', 0, 500),
        ]);
    }
}
