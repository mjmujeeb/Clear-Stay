<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // If not authenticated or running in console / seeder without session, don't scope
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            return;
        }

        $hotelId = session('current_hotel_id');

        if (!$hotelId && Auth::check()) {
            $user = Auth::user();
            // Superadmin without explicit hotel switch can see all
            if ($user->role === 'superadmin') {
                return;
            }
            $hotelId = $user->hotel_id;
        }

        if ($hotelId) {
            $builder->where($model->getTable() . '.hotel_id', $hotelId);
        }
    }
}
