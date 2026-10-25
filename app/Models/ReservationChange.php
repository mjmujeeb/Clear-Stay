<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationChange extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'reservation_id',
        'hotel_id',
        'user_id',
        'old_check_in_date',
        'old_check_out_date',
        'new_check_in_date',
        'new_check_out_date',
        'old_room_id',
        'new_room_id',
        'old_total_price',
        'new_total_price',
        'reason',
        'ip_address',
    ];

    protected $casts = [
        'old_check_in_date' => 'date',
        'old_check_out_date' => 'date',
        'new_check_in_date' => 'date',
        'new_check_out_date' => 'date',
        'old_total_price' => 'decimal:2',
        'new_total_price' => 'decimal:2',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oldRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'old_room_id');
    }

    public function newRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'new_room_id');
    }
}
