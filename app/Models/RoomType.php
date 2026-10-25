<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'name',
        'code',
        'capacity',
        'base_price',
        'description',
        'amenities',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'base_price' => 'decimal:2',
        'amenities' => 'array',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
