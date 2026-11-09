<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'adapter_class',
        'is_active',
        'api_endpoint',
        'settings',
        'last_sync_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'last_sync_at' => 'datetime',
    ];

    public function connections(): HasMany
    {
        return $this->hasMany(ChannelConnection::class);
    }
}
