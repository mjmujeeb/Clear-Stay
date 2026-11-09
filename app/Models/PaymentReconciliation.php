<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliation extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'reservation_id',
        'reservation_reference',
        'payment_reference',
        'channel_id',
        'payment_gateway',
        'payment_method',
        'expected_amount',
        'received_amount',
        'discrepancy_amount',
        'currency',
        'settlement_status',
        'reconciliation_status',
        'transaction_date',
        'settled_at',
        'exception_reason',
        'audit_history',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'discrepancy_amount' => 'decimal:2',
        'transaction_date' => 'date',
        'settled_at' => 'datetime',
        'audit_history' => 'array',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
