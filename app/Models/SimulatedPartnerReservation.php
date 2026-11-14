<?php

namespace App\Models;

use App\Casts\NormalizedDateCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulatedPartnerReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_code',
        'external_reservation_id',
        'property_code',
        'remote_room_code',
        'guest_name',
        'guest_email',
        'guest_phone',
        'check_in_date',
        'check_out_date',
        'guest_count',
        'total_amount',
        'currency',
        'status',
        'idempotency_key',
        'raw_payload',
        'clearstay_reservation_id',
    ];

    protected $casts = [
        'check_in_date' => NormalizedDateCast::class,
        'check_out_date' => NormalizedDateCast::class,
        'guest_count' => 'integer',
        'total_amount' => 'float',
        'raw_payload' => 'array',
    ];

    public function clearstayReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'clearstay_reservation_id');
    }
}
