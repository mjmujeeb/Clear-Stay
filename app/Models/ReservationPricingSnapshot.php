<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationPricingSnapshot extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'reservation_id',
        'currency',
        'base_total',
        'surcharge_total',
        'discount_total',
        'tax_total',
        'grand_total',
        'pricing_rule_version',
        'nightly_breakdown',
        'calculated_at',
    ];

    protected $casts = [
        'base_total' => 'decimal:2',
        'surcharge_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'nightly_breakdown' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
