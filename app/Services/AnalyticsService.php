<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get KPI summary cards for the dashboard.
     */
    public function getKpis(int $hotelId): array
    {
        $today = Carbon::today()->toDateString();

        $totalRooms = Room::withoutGlobalScopes()->where('hotel_id', $hotelId)->count();

        $occupiedRooms = Room::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('is_occupied', true)
            ->count();

        $dirtyRooms = Room::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->whereIn('status', ['dirty', 'inspecting'])
            ->count();

        $availableRooms = max(0, $totalRooms - $occupiedRooms - $dirtyRooms);

        $todayArrivals = Reservation::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->whereDate('check_in_date', $today)
            ->where('status', 'confirmed')
            ->count();

        $todayDepartures = Reservation::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->whereDate('check_out_date', $today)
            ->where('status', 'checked_in')
            ->count();

        // Active stays today for occupancy calculation
        $activeStaysToday = Reservation::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_in_date', '<=', $today)
            ->where('check_out_date', '>', $today)
            ->count();

        $occupancyRate = $totalRooms > 0 ? round(($activeStaysToday / $totalRooms) * 100, 1) : 0;

        // Current month revenue
        $currentMonthStart = Carbon::now()->startOfMonth()->toDateString();
        $currentMonthEnd = Carbon::now()->endOfMonth()->toDateString();

        $monthlyRevenue = Reservation::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereBetween('check_in_date', [$currentMonthStart, $currentMonthEnd])
            ->sum('total_price');

        // ADR (Average Daily Rate) = Total Room Revenue / Number of Rooms Sold
        $roomsSoldThisMonth = Reservation::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereBetween('check_in_date', [$currentMonthStart, $currentMonthEnd])
            ->count();

        $adr = $roomsSoldThisMonth > 0 ? round($monthlyRevenue / $roomsSoldThisMonth, 2) : 0;

        // RevPAR (Revenue Per Available Room) = Total Revenue / Total Available Room Nights
        $daysInMonth = Carbon::now()->daysInMonth;
        $totalRoomNightsAvailable = $totalRooms * $daysInMonth;
        $revPar = $totalRoomNightsAvailable > 0 ? round($monthlyRevenue / $totalRoomNightsAvailable, 2) : 0;

        return [
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupiedRooms,
            'available_rooms' => $availableRooms,
            'dirty_rooms' => $dirtyRooms,
            'today_arrivals' => $todayArrivals,
            'today_departures' => $todayDepartures,
            'occupancy_rate' => $occupancyRate,
            'monthly_revenue' => $monthlyRevenue,
            'adr' => $adr,
            'revpar' => $revPar,
        ];
    }

    /**
     * Get Occupancy Trend for Chart.js (past 7 or 14 days + next 7 days).
     */
    public function getOccupancyTrend(int $hotelId, int $daysBack = 7, int $daysForward = 7): array
    {
        $totalRooms = Room::withoutGlobalScopes()->where('hotel_id', $hotelId)->count();
        if ($totalRooms === 0) {
            $totalRooms = 1;
        }

        $labels = [];
        $occupancyData = [];
        $roomsSoldData = [];

        $startDate = Carbon::today()->subDays($daysBack);
        $endDate = Carbon::today()->addDays($daysForward);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayStr = $date->toDateString();
            $labels[] = $date->format('M d (D)');

            $activeCount = Reservation::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->where('check_in_date', '<=', $dayStr)
                ->where('check_out_date', '>', $dayStr)
                ->count();

            $roomsSoldData[] = $activeCount;
            $occupancyData[] = round(($activeCount / $totalRooms) * 100, 1);
        }

        return [
            'labels' => $labels,
            'occupancy_percentages' => $occupancyData,
            'rooms_sold' => $roomsSoldData,
            'total_rooms' => $totalRooms,
        ];
    }

    /**
     * Monthly Revenue chart data for past 6 months.
     */
    public function getMonthlyRevenue(int $hotelId, int $months = 6): array
    {
        $labels = [];
        $revenueData = [];
        $reservationsCountData = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth()->toDateString();
            $monthEnd = $month->copy()->endOfMonth()->toDateString();

            $labels[] = $month->format('M Y');

            $revenue = Reservation::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->whereBetween('check_in_date', [$monthStart, $monthEnd])
                ->sum('total_price');

            $count = Reservation::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->whereBetween('check_in_date', [$monthStart, $monthEnd])
                ->count();

            $revenueData[] = round($revenue, 2);
            $reservationsCountData[] = $count;
        }

        return [
            'labels' => $labels,
            'revenue' => $revenueData,
            'reservations_count' => $reservationsCountData,
        ];
    }

    /**
     * Room Type distribution & popularity.
     */
    public function getRoomTypeDistribution(int $hotelId): array
    {
        $roomTypes = RoomType::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->withCount('rooms')
            ->get();

        $labels = [];
        $roomCounts = [];
        $bookingCounts = [];

        foreach ($roomTypes as $type) {
            $labels[] = $type->name;
            $roomCounts[] = $type->rooms_count;

            $bookings = Reservation::withoutGlobalScopes()
                ->where('hotel_id', $hotelId)
                ->whereHas('room', function ($q) use ($type) {
                    $q->where('room_type_id', $type->id);
                })
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->count();

            $bookingCounts[] = $bookings;
        }

        return [
            'labels' => $labels,
            'room_counts' => $roomCounts,
            'booking_counts' => $bookingCounts,
        ];
    }
}
