<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'room_id',
        'reservation_id',
        'stay_date',
        'previous_availability',
        'new_availability',
        'delta',
        'operation_type',
        'source',
        'actor_type',
        'actor_user_id',
        'correlation_id',
        'idempotency_key',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stay_date' => 'date',
            'previous_availability' => 'integer',
            'new_availability' => 'integer',
            'delta' => 'integer',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
