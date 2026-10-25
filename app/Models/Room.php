<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'room_number',
        'floor',
        'status', // clean, dirty, inspecting, maintenance
        'is_occupied',
        'notes',
    ];

    protected $casts = [
        'floor' => 'integer',
        'is_occupied' => 'boolean',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function housekeepingTasks(): HasMany
    {
        return $this->hasMany(HousekeepingTask::class);
    }

    /**
     * Check if this room is available for the given date range.
     * Note: In standard hotel management, a guest checks out at 11:00 and another checks in at 15:00 on the same date.
     * Thus, overlap is check_in_date < $newCheckOut AND check_out_date > $newCheckIn.
     */
    public function isAvailableFor(string $checkInDate, string $checkOutDate, ?int $excludeReservationId = null): bool
    {
        $query = $this->reservations()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_in_date', '<', $checkOutDate)
            ->where('check_out_date', '>', $checkInDate);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return !$query->exists();
    }
}
