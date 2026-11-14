<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Standard OTA commission schedule rates.
     */
    protected array $commissionRates = [
        'booking.com' => 0.15,
        'agoda' => 0.15,
        'expedia' => 0.18,
        'rakuten' => 0.10,
        'rakuten travel' => 0.10,
        'jalan' => 0.10,
        'jalan.net' => 0.10,
        'direct' => 0.00,
        'direct web' => 0.00,
    ];

    /**
     * Get comprehensive hotel financial & operational KPI summary cards.
     */
    public function getKpis(int $hotelId): array
    {
        $today = Carbon::today()->toDateString();

        $totalRooms = Room::withoutGlobalScopes()->where('hotel_id', $hotelId)->count();

        $occupiedRooms = Room::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('is_occupied', true)
            ->count();

        $cleanRooms = Room::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('status', 'clean')
            ->count();

        $dirtyRooms = Room::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('status', 'dirty')
            ->count();

        $maintenanceRooms = Room::withoutGlobalScopes()
            ->where('hotel_id', $hotelId)
            ->where('status', 'maintenance')
            ->count();

        $availableRooms = max(0, $totalRooms - $occupiedRooms - $dirtyRooms - $maintenanceRooms);

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

        $occupancyRate = $totalRooms > 0 ? round(($activeStaysToday / $totalRooms) * 100, 1) : 0.0;

        // Current month revenue
        $currentMonthStart = Carbon::now()->startOfMonth()->toDateString();
        $currentMonthEnd = Carbon::now()->endOfMonth()->toDateString();

        $monthReservations = Reservation::withoutGlobalScopes()
            ->with('channel')
            ->where('hotel_id', $hotelId)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereBetween('check_in_date', [$currentMonthStart, $currentMonthEnd])
            ->get();

        $monthlyGrossRevenue = (float) $monthReservations->sum('total_price');

        // Calculate commission deductions and net realized revenue
        $totalCommissionExpense = 0.0;
        foreach ($monthReservations as $res) {
            $chName = strtolower(trim($res->channel?->name ?? 'direct'));
            $rate = $this->resolveCommissionRate($chName);
            $totalCommissionExpense += (float) ($res->total_price * $rate);
        }

        $monthlyNetRevenue = max(0.0, $monthlyGrossRevenue - $totalCommissionExpense);

        // ADR (Average Daily Rate) = Total Room Revenue / Number of Rooms Sold
        $roomsSoldThisMonth = $monthReservations->count();
        $adr = $roomsSoldThisMonth > 0 ? round($monthlyGrossRevenue / $roomsSoldThisMonth, 2) : 0.0;

        // RevPAR (Revenue Per Available Room) = Total Revenue / Total Available Room Nights
        $daysInMonth = Carbon::now()->daysInMonth;
        $totalRoomNightsAvailable = $totalRooms * $daysInMonth;
        $revPar = $totalRoomNightsAvailable > 0 ? round($monthlyGrossRevenue / $totalRoomNightsAvailable, 2) : 0.0;

        // Housekeeping tasks completed today
        $tasksCompletedToday = HousekeepingTask::where('hotel_id', $hotelId)
            ->whereDate('completed_at', $today)
            ->count();

        return [
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupiedRooms,
            'available_rooms' => $availableRooms,
            'clean_rooms' => $cleanRooms,
            'dirty_rooms' => $dirtyRooms,
            'maintenance_rooms' => $maintenanceRooms,
            'today_arrivals' => $todayArrivals,
            'today_departures' => $todayDepartures,
            'occupancy_rate' => $occupancyRate,
            'monthly_revenue' => $monthlyGrossRevenue,
            'gross_revenue' => $monthlyGrossRevenue,
            'net_revenue' => round($monthlyNetRevenue, 2),
            'commission_expense' => round($totalCommissionExpense, 2),
            'rooms_sold' => $roomsSoldThisMonth,
            'adr' => $adr,
            'revpar' => $revPar,
            'tasks_completed_today' => $tasksCompletedToday,
        ];
    }

    /**
     * Resolve commission percentage based on channel identifier.
     */
    protected function resolveCommissionRate(string $channelName): float
    {
        foreach ($this->commissionRates as $key => $rate) {
            if (str_contains($channelName, $key)) {
                return $rate;
            }
        }
        return 0.15; // default 15% OTA rate
    }

    /**
     * Calculate Channel Net Profitability and Commission Deductions.
     */
    public function getChannelProfitability(int $hotelId): array
    {
        $channels = Channel::where('is_active', true)->get();
        $profitability = [];

        foreach ($channels as $channel) {
            $reservations = Reservation::where('hotel_id', $hotelId)
                ->where('channel_id', $channel->id)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->get();

            $bookingCount = $reservations->count();
            $grossRevenue = (float) $reservations->sum('total_price');

            $rate = $this->resolveCommissionRate(strtolower($channel->name));
            $commissionAmount = round($grossRevenue * $rate, 2);
            $netRevenue = round($grossRevenue - $commissionAmount, 2);
            $marginPercent = $grossRevenue > 0 ? round(($netRevenue / $grossRevenue) * 100, 1) : 100.0;

            $profitability[] = [
                'channel_id' => $channel->id,
                'channel_name' => $channel->name,
                'channel_code' => $channel->code,
                'booking_count' => $bookingCount,
                'gross_revenue' => $grossRevenue,
                'commission_rate' => round($rate * 100, 1),
                'commission_amount' => $commissionAmount,
                'net_revenue' => $netRevenue,
                'margin_percent' => $marginPercent,
            ];
        }

        // Direct Web bookings
        $directReservations = Reservation::where('hotel_id', $hotelId)
            ->whereNull('channel_id')
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->get();

        if ($directReservations->isNotEmpty()) {
            $directGross = (float) $directReservations->sum('total_price');
            $profitability[] = [
                'channel_id' => 0,
                'channel_name' => 'Direct Web (自社サイト)',
                'channel_code' => 'DIRECT_WEB',
                'booking_count' => $directReservations->count(),
                'gross_revenue' => $directGross,
                'commission_rate' => 0.0,
                'commission_amount' => 0.0,
                'net_revenue' => $directGross,
                'margin_percent' => 100.0,
            ];
        }

        return $profitability;
    }

    /**
     * Housekeeping and Asset Operations Efficiency.
     */
    public function getHousekeepingOperationsMetrics(int $hotelId): array
    {
        $totalRooms = Room::where('hotel_id', $hotelId)->count();
        $cleanRooms = Room::where('hotel_id', $hotelId)->where('status', 'clean')->count();
        $dirtyRooms = Room::where('hotel_id', $hotelId)->where('status', 'dirty')->count();
        $inspectingRooms = Room::where('hotel_id', $hotelId)->where('status', 'inspecting')->count();
        $maintenanceRooms = Room::where('hotel_id', $hotelId)->where('status', 'maintenance')->count();

        $today = Carbon::today()->toDateString();
        $tasksToday = HousekeepingTask::where('hotel_id', $hotelId)
            ->whereDate('created_at', $today)
            ->count();
        $tasksCompletedToday = HousekeepingTask::where('hotel_id', $hotelId)
            ->where('status', 'completed')
            ->whereDate('updated_at', $today)
            ->count();

        $readinessScore = $totalRooms > 0 ? round(($cleanRooms / $totalRooms) * 100, 1) : 100.0;

        return [
            'total_rooms' => $totalRooms,
            'clean_rooms' => $cleanRooms,
            'dirty_rooms' => $dirtyRooms,
            'inspecting_rooms' => $inspectingRooms,
            'maintenance_rooms' => $maintenanceRooms,
            'tasks_today' => $tasksToday,
            'tasks_completed_today' => $tasksCompletedToday,
            'readiness_score' => $readinessScore,
        ];
    }

    /**
     * Room Type Financial Yield and Performance.
     */
    public function getRoomTypePerformance(int $hotelId): array
    {
        $roomTypes = RoomType::where('hotel_id', $hotelId)->withCount('rooms')->get();
        $performance = [];

        foreach ($roomTypes as $type) {
            $reservations = Reservation::where('hotel_id', $hotelId)
                ->where('room_type_id', $type->id)
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
                ->get();

            $soldCount = $reservations->count();
            $revenue = (float) $reservations->sum('total_price');
            $adr = $soldCount > 0 ? round($revenue / $soldCount, 2) : (float) $type->base_price;

            $performance[] = [
                'room_type_id' => $type->id,
                'name' => $type->name,
                'total_rooms' => $type->rooms_count,
                'base_price' => (float) $type->base_price,
                'bookings_sold' => $soldCount,
                'total_revenue' => $revenue,
                'realized_adr' => $adr,
            ];
        }

        return $performance;
    }

    /**
     * Generate deterministic executive analytical commentary.
     */
    public function getExecutiveInsights(int $hotelId, array $kpis): array
    {
        $insights = [];

        // Insight 1: Net Revenue Retention
        if ($kpis['gross_revenue'] > 0) {
            $retentionRate = round(($kpis['net_revenue'] / $kpis['gross_revenue']) * 100, 1);
            $insights[] = [
                'type' => 'finance',
                'title' => '純売上保持率 (Net Margin Retention)',
                'text' => "当月の純売上保持率は {$retentionRate}% です。OTA手数料控除後の手残り額は ¥" . number_format($kpis['net_revenue']) . " です。",
            ];
        }

        // Insight 2: Occupancy & ADR Health
        if ($kpis['occupancy_rate'] >= 75) {
            $insights[] = [
                'type' => 'occupancy',
                'title' => '高稼働トレンド (Strong Occupancy)',
                'text' => "現在の稼働率は {$kpis['occupancy_rate']}% と堅調です。週末および繁忙日のダイナミックプライシング引き上げを推奨します。",
            ];
        } else {
            $insights[] = [
                'type' => 'occupancy',
                'title' => '販売促進の余地 (Availability Opportunity)',
                'text' => "現在の稼働率は {$kpis['occupancy_rate']}% です。直前割引きやインバウンドOTAへのプロモーション設定を検討してください。",
            ];
        }

        // Insight 3: Operations
        if ($kpis['dirty_rooms'] > 0) {
            $insights[] = [
                'type' => 'operations',
                'title' => '客室清掃ターンオーバー',
                'text' => "清掃待ちの部屋が {$kpis['dirty_rooms']}室 あります。次のチェックインに向けて清掃スタッフの配置を調整してください。",
            ];
        } else {
            $insights[] = [
                'type' => 'operations',
                'title' => '客室アセット準備完了',
                'text' => '清掃待ちの客室はなく、すべての空室が販売可能（Clean）な状態に整備されています。',
            ];
        }

        return $insights;
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
