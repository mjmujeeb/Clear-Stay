<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class NormalizedDateCast implements CastsAttributes
{
    /**
     * Cast the given value when accessing.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        // Return a clean Carbon instance set to midnight
        return Carbon::parse($value)->startOfDay();
    }

    /**
     * Prepare the given value for storage.
     * Ensures strict YYYY-MM-DD format regardless of SQLite/MySQL/PostgreSQL.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }
}
