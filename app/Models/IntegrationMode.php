<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationMode extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_code',
        'mode',
        'activated_by',
        'credentials_verified_at',
        'notes',
    ];

    protected $casts = [
        'credentials_verified_at' => 'datetime',
    ];

    public const MODE_SIMULATOR = 'simulator';
    public const MODE_SANDBOX = 'sandbox';
    public const MODE_LIVE = 'live';

    public function user()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }
}
