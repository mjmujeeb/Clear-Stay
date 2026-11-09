<?php

namespace App\Http\Controllers;

use App\Models\AiGovernanceRecommendation;
use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\Hotel;
use App\Models\IntegrationPartner;
use App\Models\IntegrationTestRun;
use App\Models\RateParityObservation;
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
        $availableRoomsToday = $availableRooms;

        // 3. Multi-Channel synchronization health
        $channels = Channel::where('is_active', true)->get();
        $connectedChannels = ChannelConnection::where('hotel_id', $hotelId)->with('channel')->get();
        $activeChannelsCount = ChannelConnection::where('hotel_id', $hotelId)->where('is_enabled', true)->count();
        if ($activeChannelsCount === 0 && $channels->count() > 0) {
            $activeChannelsCount = $channels->count();
        }

        $pendingOutboxCount = SyncOutboxEvent::where('hotel_id', $hotelId)
            ->where('status', 'pending')
            ->count();

        $failedOutboxCount = SyncOutboxEvent::where('hotel_id', $hotelId)
            ->where('status', 'failed')
            ->count();

        $failingSyncsCount = $failedOutboxCount;

        // 4. Rate Parity Observations
        $parityObservations = RateParityObservation::where('hotel_id', $hotelId)
            ->with(['roomType', 'channel'])
            ->latest('observed_at')
            ->take(5)
            ->get();

        // 5. System Risk Posture (0-100)
        $latestRisk = SecurityRiskEvent::where('hotel_id', $hotelId)->latest()->first();
        $systemRiskScore = $latestRisk ? $latestRisk->computed_risk_score : 18;

        // 6. AI Security Recommendations
        $recommendations = AiGovernanceRecommendation::where('hotel_id', $hotelId)
            ->where('status', 'pending')
            ->with('user')
            ->latest()
            ->take(3)
            ->get();

        // 7. Certified Integration Partners
        $partners = IntegrationPartner::where(function ($q) {
            $q->where('status', 'certified')->orWhere('status', 'active');
        })->take(4)->get();

        // 8. Integration Assurance status
        $recentTestRuns = IntegrationTestRun::latest()->take(3)->with(['suite', 'partner'])->get();
        $failedTestsCount = IntegrationTestRun::where('status', 'failed')->count();

        // 9. Security Incident Count
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
            'availableRoomsToday',
            'channels',
            'connectedChannels',
            'activeChannelsCount',
            'pendingOutboxCount',
            'failedOutboxCount',
            'failingSyncsCount',
            'parityObservations',
            'systemRiskScore',
            'recommendations',
            'partners',
            'recentTestRuns',
            'failedTestsCount',
            'criticalSecurityCount'
        ));
    }
}
