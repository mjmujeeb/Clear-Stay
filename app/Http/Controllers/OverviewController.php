<?php

namespace App\Services;

namespace App\Http\Controllers;

use App\Models\CentralInventory;
use App\Models\ChannelConnection;
use App\Models\Hotel;
use App\Models\IntegrationTestRun;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\SecurityIncident;
use App\Models\SecurityRiskEvent;
use App\Models\SyncOutboxEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OverviewController extends Controller
{
    /**
     * ClearStay 2.0 Command Center Overview
     * Answers: What is happening today? Arrivals, Available rooms, failing syncs, testing status, security alerts.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId) ?? Hotel::first();
        if ($hotel && !session('current_hotel_id')) {
            session(['current_hotel_id' => $hotel->id]);
            $hotelId = $hotel->id;
        }

        $today = Carbon::today()->toDateString();

        // 1. Operational arrivals & departures
        $todayArrivals = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_in_date', $today)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->with(['guest', 'room'])
            ->get();

        $todayDepartures = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_out_date', $today)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->with(['guest', 'room'])
            ->get();

        // 2. Room & Central Inventory summary
        $totalRooms = Room::where('hotel_id', $hotelId)->count();
        $occupiedRooms = Room::where('hotel_id', $hotelId)->where('is_occupied', true)->count();
        $availableRooms = max(0, $totalRooms - $occupiedRooms);

        // 3. Multi-Channel synchronization health
        $connectedChannels = ChannelConnection::where('hotel_id', $hotelId)->with('channel')->get();
        $failingSyncsCount = SyncOutboxEvent::where('hotel_id', $hotelId)
            ->where('status', 'failed')
            ->count();

        // 4. Integration Assurance status
        $recentTestRuns = IntegrationTestRun::latest()->take(3)->with(['suite', 'partner'])->get();
        $failedTestsCount = IntegrationTestRun::where('status', 'failed')->count();

        // 5. Intelligent Security Alerts
        $unreviewedRisks = SecurityRiskEvent::where('hotel_id', $hotelId)
            ->where('is_reviewed', false)
            ->latest()
            ->take(4)
            ->get();

        $criticalSecurityCount = SecurityIncident::where('hotel_id', $hotelId)
            ->whereIn('severity', ['high', 'critical'])
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return view('overview.index', compact(
            'hotel',
            'today',
            'todayArrivals',
            'todayDepartures',
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'connectedChannels',
            'failingSyncsCount',
            'recentTestRuns',
            'failedTestsCount',
            'unreviewedRisks',
            'criticalSecurityCount'
        ));
    }
}
