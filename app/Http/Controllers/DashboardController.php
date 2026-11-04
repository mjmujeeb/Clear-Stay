<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index()
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId);

        if (!$hotel) {
            $hotel = Hotel::where('is_active', true)->first();
            if ($hotel) {
                session(['current_hotel_id' => $hotel->id]);
                $hotelId = $hotel->id;
            }
        }

        $allHotels = Auth::user()->isSuperAdmin() ? Hotel::where('is_active', true)->get() : collect([$hotel]);

        $kpis = $hotelId ? $this->analyticsService->getKpis($hotelId) : [
            'total_rooms' => 0,
            'occupied_rooms' => 0,
            'available_rooms' => 0,
            'dirty_rooms' => 0,
            'today_arrivals' => 0,
            'today_departures' => 0,
            'occupancy_rate' => 0,
            'monthly_revenue' => 0,
            'adr' => 0,
            'revpar' => 0,
        ];

        $today = Carbon::today()->toDateString();

        // Today's arrivals
        $todayArrivals = Reservation::with(['guest', 'room.roomType'])
            ->where('hotel_id', $hotelId)
            ->whereDate('check_in_date', $today)
            ->where('status', 'confirmed')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        // Today's departures
        $todayDepartures = Reservation::with(['guest', 'room.roomType'])
            ->where('hotel_id', $hotelId)
            ->whereDate('check_out_date', $today)
            ->where('status', 'checked_in')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        // Urgent housekeeping tasks
        $urgentTasks = HousekeepingTask::with(['room', 'assignee'])
            ->where('hotel_id', $hotelId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->take(5)
            ->get();

        // Recent audit logs
        $recentLogs = AuditLog::with('user')
            ->where(function ($q) use ($hotelId) {
                $q->where('hotel_id', $hotelId)->orWhereNull('hotel_id');
            })
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'hotel',
            'allHotels',
            'kpis',
            'todayArrivals',
            'todayDepartures',
            'urgentTasks',
            'recentLogs'
        ));
    }
}
