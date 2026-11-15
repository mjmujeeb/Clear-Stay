<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Services\Inventory\RoomAvailabilityMatrixService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoomAvailabilityMatrixController extends Controller
{
    protected RoomAvailabilityMatrixService $matrixService;

    public function __construct(RoomAvailabilityMatrixService $matrixService)
    {
        $this->matrixService = $matrixService;
    }

    /**
     * Display the Live Room Availability Matrix hub.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = $hotelId ? Hotel::find($hotelId) : Hotel::where('is_active', true)->first();
        if ($hotel && session('current_hotel_id') !== $hotel->id) {
            session(['current_hotel_id' => $hotel->id]);
        }
        $hotelId = $hotel?->id ?? 1;

        $viewMode = $request->input('view', 'single'); // 'single' or 'multi'
        $stayDate = $request->input('stay_date', Carbon::today()->addDay()->format('Y-m-d'));
        $checkInDate = $request->input('check_in_date', Carbon::today()->addDay()->format('Y-m-d'));
        $checkOutDate = $request->input('check_out_date', Carbon::today()->addDays(3)->format('Y-m-d'));
        $requestedRooms = (int) $request->input('requested_rooms', 1);

        $roomTypeId = $request->filled('room_type_id') ? (int) $request->input('room_type_id') : null;
        $channelCode = $request->filled('channel_code') ? $request->input('channel_code') : null;
        $statusFilter = $request->input('status_filter', 'all');

        $hotels = Hotel::where('is_active', true)->get();
        $roomTypes = RoomType::where('hotel_id', $hotelId)->get();
        $channels = Channel::where('is_active', true)->get();

        if ($viewMode === 'multi') {
            $matrixData = $this->matrixService->getMatrixForStayRange(
                $hotelId,
                $checkInDate,
                $checkOutDate,
                $requestedRooms,
                $roomTypeId,
                $channelCode
            );
        } else {
            $matrixData = $this->matrixService->getMatrixForDate(
                $hotelId,
                $stayDate,
                $roomTypeId,
                $channelCode,
                $statusFilter
            );
        }

        return view('room_availability.index', compact(
            'hotel',
            'hotels',
            'roomTypes',
            'channels',
            'viewMode',
            'stayDate',
            'checkInDate',
            'checkOutDate',
            'requestedRooms',
            'roomTypeId',
            'channelCode',
            'statusFilter',
            'matrixData'
        ));
    }

    /**
     * AJAX endpoint: Retrieve live matrix JSON payload for instant reactive filtering.
     */
    public function matrixData(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id ?? 1;
        $viewMode = $request->input('view', 'single');
        $stayDate = $request->input('stay_date', Carbon::today()->addDay()->format('Y-m-d'));
        $checkInDate = $request->input('check_in_date', Carbon::today()->addDay()->format('Y-m-d'));
        $checkOutDate = $request->input('check_out_date', Carbon::today()->addDays(3)->format('Y-m-d'));
        $requestedRooms = (int) $request->input('requested_rooms', 1);
        $roomTypeId = $request->filled('room_type_id') ? (int) $request->input('room_type_id') : null;
        $channelCode = $request->filled('channel_code') ? $request->input('channel_code') : null;
        $statusFilter = $request->input('status_filter', 'all');

        if ($viewMode === 'multi') {
            $data = $this->matrixService->getMatrixForStayRange(
                $hotelId,
                $checkInDate,
                $checkOutDate,
                $requestedRooms,
                $roomTypeId,
                $channelCode
            );
        } else {
            $data = $this->matrixService->getMatrixForDate(
                $hotelId,
                $stayDate,
                $roomTypeId,
                $channelCode,
                $statusFilter
            );
        }

        return response()->json([
            'success' => true,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'matrix' => $data,
        ]);
    }

    /**
     * AJAX endpoint: Retrieve detailed quantity breakdown for interactive cell modal.
     */
    public function quantityDetails(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id ?? 1;

        $validated = $request->validate([
            'room_type_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'channel_code' => 'nullable|string',
        ]);

        $details = $this->matrixService->getQuantityDetails(
            $hotelId,
            (int) $validated['room_type_id'],
            $validated['date'],
            $validated['channel_code'] ?? null
        );

        return response()->json([
            'success' => true,
            'details' => $details,
        ]);
    }

    /**
     * AJAX action: Re-align channel simulator inventory to match Central PMS.
     */
    public function realignChannel(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id ?? 1;

        $validated = $request->validate([
            'room_type_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'partner_code' => 'required|string',
        ]);

        $result = $this->matrixService->realignChannelInventory(
            $hotelId,
            (int) $validated['room_type_id'],
            $validated['date'],
            $validated['partner_code'],
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Export live room availability report in CSV or print layout.
     */
    public function export(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id ?? 1;
        $hotel = Hotel::find($hotelId);
        $stayDate = $request->input('stay_date', Carbon::today()->addDay()->format('Y-m-d'));
        $format = $request->input('format', 'csv');

        $matrix = $this->matrixService->getMatrixForDate($hotelId, $stayDate);

        if ($format === 'print') {
            return view('room_availability.print', [
                'hotel' => $hotel,
                'matrixData' => $matrix,
                'stayDate' => $stayDate,
            ]);
        }

        $filename = "clearstay_availability_{$hotelId}_{$stayDate}.csv";

        return new StreamedResponse(function () use ($matrix, $stayDate, $hotel) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel
            fputs($handle, "\xEF\xBB\xBF");

            // Meta headers
            fputcsv($handle, ['ClearStay Enterprise Hospitality - Live Room Availability Report']);
            fputcsv($handle, ['Property', $hotel?->name ?? 'Primary Property', 'Date', $stayDate, 'Generated At', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            // Column Headers
            $header = [
                'Room Type Code',
                'Room Type Name',
                'Physical Total',
                'PMS Reserved',
                'PMS Maintenance',
                'PMS Available',
            ];

            foreach ($matrix['channels'] as $ch) {
                $header[] = "{$ch->name} [SIMULATED]";
            }
            $header[] = 'Overall Parity Status';

            fputcsv($handle, $header);

            // Data Rows
            foreach ($matrix['rows'] as $row) {
                $line = [
                    $row['room_type_code'],
                    $row['room_type_name'],
                    $row['pms']['total_units'],
                    $row['pms']['reserved_units'],
                    $row['pms']['maintenance_units'],
                    $row['pms']['available_units'],
                ];

                foreach ($matrix['channels'] as $ch) {
                    $chData = $row['channels'][$ch->code] ?? null;
                    $line[] = $chData ? ($chData['remote_units'] !== null ? $chData['remote_units'] : $chData['status_label']) : 'N/A';
                }

                $status = 'MATCHED';
                if ($row['has_oversell_risk']) {
                    $status = 'CRITICAL OVERSELL RISK';
                } elseif ($row['has_discrepancy']) {
                    $status = 'DISCREPANCY DETECTED';
                }
                $line[] = $status;

                fputcsv($handle, $line);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
