<?php

namespace App\Http\Controllers;

use App\Models\BookingSnapshot;
use App\Models\Channel;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Services\Analytics\BookingAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingAnalyticsController extends Controller
{
    protected BookingAnalyticsService $analyticsService;

    public function __construct(BookingAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * TEMAIRAZU JIDO BA+ Style Advanced Booking Analytics Hub
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId) ?? Hotel::first();

        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->addDays(30)->toDateString());
        $roomTypeId = $request->input('room_type_id');
        $channelId = $request->input('channel_id');
        $status = $request->input('status');
        $targetStayDate = $request->input('target_stay_date', Carbon::today()->addDays(7)->toDateString());

        // 1. Core KPIs (Occupancy, ADR, RevPAR, Revenue)
        $metrics = $this->analyticsService->getReservationStatusMetrics(
            $hotelId,
            $startDate,
            $endDate,
            $roomTypeId ? (int)$roomTypeId : null,
            $channelId ? (int)$channelId : null,
            $status
        );

        // 2. Historical comparison across 4 horizons
        $historicalComparison = $this->analyticsService->getHistoricalComparison($hotelId, $targetStayDate);

        // 3. Booking Pace curve (lead-time accumulation)
        $bookingPace = $this->analyticsService->getBookingPaceData($hotelId, 30);

        // 4. Channel distribution & contribution
        $channelDistribution = $this->analyticsService->getChannelDistribution($hotelId);

        // 5. Recent snapshots table
        $recentSnapshots = BookingSnapshot::where('hotel_id', $hotelId)
            ->latest('snapshot_date')
            ->take(10)
            ->get();

        $roomTypes = RoomType::where('hotel_id', $hotelId)->get();
        $channels = Channel::where('is_active', true)->get();

        return view('analytics.index', compact(
            'hotel',
            'startDate',
            'endDate',
            'roomTypeId',
            'channelId',
            'status',
            'targetStayDate',
            'metrics',
            'historicalComparison',
            'bookingPace',
            'channelDistribution',
            'recentSnapshots',
            'roomTypes',
            'channels'
        ));
    }

    /**
     * Manually capture snapshot for a target stay date.
     */
    public function capture(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::findOrFail($hotelId);

        $request->validate([
            'stay_date' => 'required|date',
        ]);

        $stayDate = Carbon::parse($request->input('stay_date'));
        $snapshot = $this->analyticsService->captureSnapshot($hotel, $stayDate);

        return back()->with('success', "Historical snapshot successfully captured for stay date: {$snapshot->stay_date->toDateString()}.");
    }

    /**
     * CSV Export of booking and cancellation statistics.
     */
    public function exportCsv(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::findOrFail($hotelId);

        $reservations = Reservation::where('hotel_id', $hotelId)
            ->with(['guest', 'room', 'channel'])
            ->latest()
            ->take(500)
            ->get();

        $filename = "booking_report_{$hotel->code}_" . now()->format('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reservations) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Japanese Excel

            fputcsv($handle, [
                'Confirmation Code',
                'Guest Name',
                'Channel',
                'Room',
                'Check In',
                'Check Out',
                'Nights',
                'Status',
                'Total Price (JPY)',
                'Created At',
            ]);

            foreach ($reservations as $res) {
                fputcsv($handle, [
                    $res->confirmation_code,
                    $res->guest?->full_name ?? $res->guest_name,
                    $res->channel?->name ?? 'Direct Web',
                    $res->room?->room_number ?? 'Unassigned',
                    $res->check_in_date?->toDateString(),
                    $res->check_out_date?->toDateString(),
                    $res->nights ?? 1,
                    $res->status,
                    $res->total_price,
                    $res->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
