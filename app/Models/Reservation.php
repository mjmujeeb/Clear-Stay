<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'guest_id',
        'room_id',
        'confirmation_code',
        'guest_portal_token',
        'check_in_date',
        'check_out_date',
        'adults',
        'children',
        'total_price',
        'payment_status',
        'status', // confirmed, checked_in, checked_out, cancelled
        'special_requests',
        'checked_in_at',
        'checked_out_at',
        'cancelled_at',
        'source',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'adults' => 'integer',
        'children' => 'integer',
        'total_price' => 'decimal:2',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            if (empty($reservation->confirmation_code)) {
                $reservation->confirmation_code = 'CS-' . strtoupper(Str::random(4)) . '-' . rand(1000, 9999);
            }
            if (empty($reservation->guest_portal_token)) {
                $reservation->guest_portal_token = Str::random(48);
            }
        });
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ReservationChange::class)->orderBy('created_at', 'desc');
    }

    public function getNightsAttribute(): int
    {
        return $this->check_in_date->diffInDays($this->check_out_date) ?: 1;
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['confirmed', 'checked_in']);
    }

    public function scopeArrivingToday(Builder $query): Builder
    {
        return $query->where('status', 'confirmed')
            ->whereDate('check_in_date', now()->toDateString());
    }

    public function scopeDepartingToday(Builder $query): Builder
    {
        return $query->where('status', 'checked_in')
            ->whereDate('check_out_date', now()->toDateString());
    }
}
