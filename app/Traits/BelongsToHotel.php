<?php

namespace App\Traits;

use App\Models\Hotel;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToHotel
{
    /**
     * Boot the BelongsToHotel trait for a model.
     */
    protected static function bootBelongsToHotel(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (empty($model->hotel_id)) {
                $hotelId = session('current_hotel_id');
                if (!$hotelId && Auth::check() && Auth::user()->hotel_id) {
                    $hotelId = Auth::user()->hotel_id;
                }
                if ($hotelId) {
                    $model->hotel_id = $hotelId;
                }
            }
        });
    }

    /**
     * Get the hotel that owns this model.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
