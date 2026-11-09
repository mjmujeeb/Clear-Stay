<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
        'description_ja',
    ];

    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }
}
