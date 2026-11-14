<?php

namespace App\Models;

use App\Traits\BelongsToHotel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory, BelongsToHotel;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'rule_name',
        'rule_name_ja',
        'rule_type',
        'percentage_adjustment',
        'fixed_adjustment',
        'start_date',
        'end_date',
        'weekdays',
        'priority',
        'is_active',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'percentage_adjustment' => 'float',
        'fixed_adjustment' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'weekdays' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
