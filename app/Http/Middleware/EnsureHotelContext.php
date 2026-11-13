<?php

namespace App\Http\Middleware;

use App\Models\Hotel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureHotelContext
{
    /**
     * Ensure that every authenticated request has an active and valid hotel context in the session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentHotelId = session('current_hotel_id');

            // 1. Check if the current session hotel is still valid
            $hotel = $currentHotelId ? Hotel::find($currentHotelId) : null;

            // 2. If not valid, attempt to use the user's assigned hotel
            if (!$hotel && $user->hotel_id) {
                $hotel = Hotel::find($user->hotel_id);
            }

            // 3. If still not valid, fallback to the first active hotel in the system
            if (!$hotel) {
                $hotel = Hotel::where('is_active', true)->first();
            }

            // 4. Store resolved hotel ID in session and share with views
            if ($hotel) {
                if (session('current_hotel_id') !== $hotel->id) {
                    session(['current_hotel_id' => $hotel->id]);
                }
                view()->share('currentHotel', $hotel);
                view()->share('currentHotelId', $hotel->id);
            }
        }

        return $next($request);
    }
}
