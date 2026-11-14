<?php

namespace App\Services\Analytics;

use App\Models\BookingSnapshot;
use App\Models\Channel;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingAnalyticsService
{
    /**
     * Compute comprehensive booking and reservation behavior metrics.
     */
    public function getReservationStatusMetrics(
        int $hotelId,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $roomTypeId = null,
        ?int $channelId = null,
        ?string $status = null
    ): array {
        $startDate = $startDate ?? Carbon::today()->toDateString();
        $endDate = $endDate ?? Carbon::today()->addDays(30)->toDateString();

        $daysCount = max(1, Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);

        // 1. Total rooms available in this property
        $roomQuery = Room::where('hotel_id', $hotelId);
        if ($roomTypeId) {
            $roomQuery->where('room_type_id', $roomTypeId);
        }
        $totalRooms = $roomQuery->count();
        $totalAvailableRoomNights = $totalRooms * $daysCount;

        // 2. Query reservations active during this window
        $resQuery = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_in_date', '<=', $endDate)
            ->whereDate('check_out_date', '>=', $startDate);

        if ($roomTypeId) {
            $resQuery->where('room_type_id', $roomTypeId);
        }
        if ($channelId) {
            $resQuery->where('channel_id', $channelId);
        }
        if ($status) {
            $resQuery->where('status', $status);
        } else {
            $resQuery->whereIn('status', ['confirmed', 'checked_in', 'checked_out']);
        }

        $activeReservations = $resQuery->get();

        $cancellationsCount = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_in_date', '<=', $endDate)
            ->whereDate('check_out_date', '>=', $startDate)
            ->where('status', 'cancelled')
            ->count();

        $roomsSold = $activeReservations->count();
        $roomRevenue = (float) $activeReservations->sum('total_price');

        // Formulas
        $occupancyRate = $totalAvailableRoomNights > 0
            ? round(($roomsSold / $totalAvailableRoomNights) * 100, 1)
            : 0.0;

        $adr = $roomsSold > 0
            ? round($roomRevenue / $roomsSold, 2)
            : 0.0;

        $revpar = $totalAvailableRoomNights > 0
            ? round($roomRevenue / $totalAvailableRoomNights, 2)
            : 0.0;

        $cancellationRate = ($roomsSold + $cancellationsCount) > 0
            ? round(($cancellationsCount / ($roomsSold + $cancellationsCount)) * 100, 1)
            : 0.0;

        // Booking Behavior Metrics
        $leadTimesSum = 0;
        $nightsSum = 0;
        foreach ($activeReservations as $r) {
            $created = Carbon::parse($r->created_at);
            $checkIn = Carbon::parse($r->check_in_date);
            $leadTimesSum += max(0, $created->diffInDays($checkIn, false));
            $nightsSum += max(1, $r->nights ?? 1);
        }

        $avgLeadTime = $roomsSold > 0 ? round($leadTimesSum / $roomsSold, 1) : 0.0;
        $avgLengthOfStay = $roomsSold > 0 ? round($nightsSum / $roomsSold, 1) : 0.0;

        // Net Pickup Velocity in Trailing 7 and 30 Days
        $createdLast7d = Reservation::where('hotel_id', $hotelId)
            ->where('created_at', '>=', Carbon::today()->subDays(7))
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->count();
        $cancelledLast7d = Reservation::where('hotel_id', $hotelId)
            ->where('created_at', '>=', Carbon::today()->subDays(7))
            ->where('status', 'cancelled')
            ->count();
        $netPickup7d = $createdLast7d - $cancelledLast7d;

        $createdLast30d = Reservation::where('hotel_id', $hotelId)
            ->where('created_at', '>=', Carbon::today()->subDays(30))
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->count();
        $cancelledLast30d = Reservation::where('hotel_id', $hotelId)
            ->where('created_at', '>=', Carbon::today()->subDays(30))
            ->where('status', 'cancelled')
            ->count();
        $netPickup30d = $createdLast30d - $cancelledLast30d;

        // Future Room Nights on the Books (OTB) for next 30 days
        $futureOtbNights = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_in_date', '>=', Carbon::today())
            ->whereDate('check_in_date', '<=', Carbon::today()->addDays(30))
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->sum('nights') ?: 0;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_rooms' => $totalRooms,
            'total_available_room_nights' => $totalAvailableRoomNights,
            'rooms_sold' => $roomsSold,
            'room_revenue' => $roomRevenue,
            'occupancy_rate' => $occupancyRate,
            'adr' => $adr,
            'revpar' => $revpar,
            'cancellations_count' => $cancellationsCount,
            'cancellation_rate' => $cancellationRate,
            'avg_lead_time' => $avgLeadTime,
            'avg_length_of_stay' => $avgLengthOfStay,
            'net_pickup_7d' => $netPickup7d,
            'net_pickup_30d' => $netPickup30d,
            'future_room_nights_otb' => $futureOtbNights,
        ];
    }

    /**
     * Generate an immutable snapshot record for a target stay date.
     */
    public function captureSnapshot(Hotel $hotel, Carbon $targetStayDate, ?Carbon $snapshotDate = null): BookingSnapshot
    {
        $snapshotDate = $snapshotDate ?? Carbon::today();
        $targetDateStr = $targetStayDate->toDateString();

        $totalRooms = Room::where('hotel_id', $hotel->id)->count();

        $reservations = Reservation::where('hotel_id', $hotel->id)
            ->whereDate('check_in_date', '<=', $targetDateStr)
            ->whereDate('check_out_date', '>', $targetDateStr)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->get();

        $cancellations = Reservation::where('hotel_id', $hotel->id)
            ->whereDate('check_in_date', '<=', $targetDateStr)
            ->whereDate('check_out_date', '>', $targetDateStr)
            ->where('status', 'cancelled')
            ->count();

        $occupied = $reservations->count();
        $available = max(0, $totalRooms - $occupied);
        $revenue = (float) $reservations->sum('total_price');

        $adr = $occupied > 0 ? round($revenue / $occupied, 2) : 0.0;
        $revpar = $totalRooms > 0 ? round($revenue / $totalRooms, 2) : 0.0;
        $occupancy = $totalRooms > 0 ? round(($occupied / $totalRooms) * 100, 1) : 0.0;

        return BookingSnapshot::updateOrCreate(
            [
                'hotel_id' => $hotel->id,
                'snapshot_date' => $snapshotDate->toDateString(),
                'stay_date' => $targetDateStr,
            ],
            [
                'booking_count' => $occupied,
                'occupied_rooms' => $occupied,
                'available_rooms' => $available,
                'revenue' => $revenue,
                'cancellation_count' => $cancellations,
                'adr' => $adr,
                'revpar' => $revpar,
                'occupancy_rate' => $occupancy,
                'metadata' => [
                    'captured_at' => now()->toIso8601String(),
                    'source' => 'temairazu_ba_engine',
                ],
            ]
        );
    }

    /**
     * Compare current booking position against historical snapshots across 4 horizons.
     */
    public function getHistoricalComparison(int $hotelId, string $targetStayDate): array
    {
        $today = Carbon::today();
        $sevenDaysAgo = Carbon::today()->subDays(7)->toDateString();
        $thirtyDaysAgo = Carbon::today()->subDays(30)->toDateString();
        $oneYearAgo = Carbon::today()->subYear()->toDateString();

        $currentSnapshot = BookingSnapshot::where('hotel_id', $hotelId)
            ->whereDate('stay_date', $targetStayDate)
            ->whereDate('snapshot_date', $today->toDateString())
            ->first();

        // If no today snapshot, generate on the fly
        if (!$currentSnapshot) {
            $hotel = Hotel::find($hotelId);
            if ($hotel) {
                $currentSnapshot = $this->captureSnapshot($hotel, Carbon::parse($targetStayDate), $today);
            }
        }

        $snap7Days = BookingSnapshot::where('hotel_id', $hotelId)
            ->whereDate('stay_date', $targetStayDate)
            ->whereDate('snapshot_date', '<=', $sevenDaysAgo)
            ->latest('snapshot_date')
            ->first();

        $snap30Days = BookingSnapshot::where('hotel_id', $hotelId)
            ->whereDate('stay_date', $targetStayDate)
            ->whereDate('snapshot_date', '<=', $thirtyDaysAgo)
            ->latest('snapshot_date')
            ->first();

        $snapPrevYear = BookingSnapshot::where('hotel_id', $hotelId)
            ->whereDate('stay_date', Carbon::parse($targetStayDate)->subYear()->toDateString())
            ->first();

        return [
            'target_stay_date' => $targetStayDate,
            'current' => $currentSnapshot,
            'seven_days_ago' => $snap7Days,
            'thirty_days_ago' => $snap30Days,
            'previous_year' => $snapPrevYear,
        ];
    }

    /**
     * Booking Pace Report: Accumulation of reservations across lead-time booking windows.
     */
    public function getBookingPaceData(int $hotelId, int $daysAhead = 30): array
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays($daysAhead);

        $reservations = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_in_date', '>=', $startDate->toDateString())
            ->whereDate('check_in_date', '<=', $endDate->toDateString())
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->get();

        $paceBuckets = [
            '60+_days' => 0,
            '31_to_60_days' => 0,
            '15_to_30_days' => 0,
            '8_to_14_days' => 0,
            '4_to_7_days' => 0,
            '1_to_3_days' => 0,
            'same_day' => 0,
        ];

        foreach ($reservations as $res) {
            $created = Carbon::parse($res->created_at);
            $checkIn = Carbon::parse($res->check_in_date);
            $leadTimeDays = max(0, $created->diffInDays($checkIn, false));

            if ($leadTimeDays >= 60) {
                $paceBuckets['60+_days']++;
            } elseif ($leadTimeDays >= 31) {
                $paceBuckets['31_to_60_days']++;
            } elseif ($leadTimeDays >= 15) {
                $paceBuckets['15_to_30_days']++;
            } elseif ($leadTimeDays >= 8) {
                $paceBuckets['8_to_14_days']++;
            } elseif ($leadTimeDays >= 4) {
                $paceBuckets['4_to_7_days']++;
            } elseif ($leadTimeDays >= 1) {
                $paceBuckets['1_to_3_days']++;
            } else {
                $paceBuckets['same_day']++;
            }
        }

        return $paceBuckets;
    }

    /**
     * Lead-Time Distribution for visual bar charting.
     */
    public function getLeadTimeDistribution(int $hotelId): array
    {
        $paceData = $this->getBookingPaceData($hotelId, 60);

        return [
            'labels' => ['Same Day (0d)', '1-3 Days', '4-7 Days', '8-14 Days', '15-30 Days', '31-60 Days', '60+ Days'],
            'data' => [
                $paceData['same_day'],
                $paceData['1_to_3_days'],
                $paceData['4_to_7_days'],
                $paceData['8_to_14_days'],
                $paceData['15_to_30_days'],
                $paceData['31_to_60_days'],
                $paceData['60+_days'],
            ],
        ];
    }

    /**
     * Length of Stay (LoS) Cohorts.
     */
    public function getLengthOfStayDistribution(int $hotelId): array
    {
        $reservations = Reservation::where('hotel_id', $hotelId)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->get();

        $cohorts = [
            '1 Night' => 0,
            '2 Nights' => 0,
            '3-4 Nights' => 0,
            '5-7 Nights' => 0,
            '8+ Nights' => 0,
        ];

        foreach ($reservations as $r) {
            $n = max(1, $r->nights ?? 1);
            if ($n === 1) {
                $cohorts['1 Night']++;
            } elseif ($n === 2) {
                $cohorts['2 Nights']++;
            } elseif ($n >= 3 && $n <= 4) {
                $cohorts['3-4 Nights']++;
            } elseif ($n >= 5 && $n <= 7) {
                $cohorts['5-7 Nights']++;
            } else {
                $cohorts['8+ Nights']++;
            }
        }

        return [
            'labels' => array_keys($cohorts),
            'data' => array_values($cohorts),
        ];
    }

    /**
     * Future 30-Day On-The-Books (OTB) Daily Demand Forecast.
     */
    public function getFutureOnTheBooks(int $hotelId, int $daysAhead = 30): array
    {
        $labels = [];
        $data = [];

        $today = Carbon::today();
        for ($i = 0; $i < $daysAhead; $i++) {
            $date = $today->copy()->addDays($i);
            $dateStr = $date->toDateString();
            $labels[] = $date->format('m/d');

            $count = Reservation::where('hotel_id', $hotelId)
                ->whereDate('check_in_date', '<=', $dateStr)
                ->whereDate('check_out_date', '>', $dateStr)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->count();

            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Channel contribution and cancellation distribution.
     */
    public function getChannelDistribution(int $hotelId): Collection
    {
        return Reservation::where('hotel_id', $hotelId)
            ->select('channel_id', DB::raw('count(*) as total_bookings'), DB::raw('sum(total_price) as total_revenue'), DB::raw("sum(case when status = 'cancelled' then 1 else 0 end) as cancellations"))
            ->groupBy('channel_id')
            ->with('channel')
            ->get();
    }
}
