<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\HousekeepingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomBoardController extends Controller
{
    protected HousekeepingService $housekeepingService;

    public function __construct(HousekeepingService $housekeepingService)
    {
        $this->housekeepingService = $housekeepingService;
    }

    /**
     * Display interactive Room Board.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId);

        $floorFilter = $request->input('floor');
        $statusFilter = $request->input('status');
        $typeFilter = $request->input('type');

        $query = Room::with(['roomType', 'reservations' => function ($q) {
            $today = Carbon::today()->toDateString();
            $q->whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in_date', '<=', $today)
                ->where('check_out_date', '>', $today)
                ->with('guest');
        }])->where('hotel_id', $hotelId);

        if ($floorFilter) {
            $query->where('floor', $floorFilter);
        }
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }
        if ($typeFilter) {
            $query->where('room_type_id', $typeFilter);
        }

        $rooms = $query->orderBy('floor')->orderBy('room_number')->get();

        // Group rooms by floor
        $floors = $rooms->groupBy('floor');

        $roomTypes = RoomType::where('hotel_id', $hotelId)->get();
        $availableFloors = Room::where('hotel_id', $hotelId)->distinct()->pluck('floor')->sort()->values();

        // Quick summary counts
        $counts = [
            'total' => Room::where('hotel_id', $hotelId)->count(),
            'clean' => Room::where('hotel_id', $hotelId)->where('status', 'clean')->count(),
            'dirty' => Room::where('hotel_id', $hotelId)->where('status', 'dirty')->count(),
            'inspecting' => Room::where('hotel_id', $hotelId)->where('status', 'inspecting')->count(),
            'maintenance' => Room::where('hotel_id', $hotelId)->where('status', 'maintenance')->count(),
            'occupied' => Room::where('hotel_id', $hotelId)->where('is_occupied', true)->count(),
        ];

        return view('board.index', compact(
            'hotel',
            'floors',
            'roomTypes',
            'availableFloors',
            'counts',
            'floorFilter',
            'statusFilter',
            'typeFilter'
        ));
    }

    /**
     * AJAX endpoint for live polling board refresh (returns json room statuses).
     */
    public function status(): JsonResponse
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $today = Carbon::today()->toDateString();

        $rooms = Room::with(['roomType', 'reservations' => function ($q) use ($today) {
            $q->whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in_date', '<=', $today)
                ->where('check_out_date', '>', $today)
                ->with('guest');
        }])->where('hotel_id', $hotelId)->get();

        $data = $rooms->map(function ($room) {
            $activeRes = $room->reservations->first();
            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'type' => $room->roomType->name,
                'status' => $room->status,
                'is_occupied' => (bool) $room->is_occupied,
                'current_guest' => $activeRes ? $activeRes->guest->full_name : null,
                'reservation_id' => $activeRes ? $activeRes->id : null,
                'confirmation_code' => $activeRes ? $activeRes->confirmation_code : null,
                'check_in' => $activeRes ? $activeRes->check_in_date->format('Y-m-d') : null,
                'check_out' => $activeRes ? $activeRes->check_out_date->format('Y-m-d') : null,
            ];
        });

        $counts = [
            'total' => $rooms->count(),
            'clean' => $rooms->where('status', 'clean')->count(),
            'dirty' => $rooms->where('status', 'dirty')->count(),
            'inspecting' => $rooms->where('status', 'inspecting')->count(),
            'maintenance' => $rooms->where('status', 'maintenance')->count(),
            'occupied' => $rooms->where('is_occupied', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'counts' => $counts,
            'rooms' => $data,
        ]);
    }

    /**
     * AJAX quick status update for a room.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:clean,dirty,inspecting,maintenance',
            'notes' => 'nullable|string|max:255',
        ]);

        $room = $this->housekeepingService->updateRoomStatus($id, $request->status, $request->notes);

        return response()->json([
            'success' => true,
            'message' => __('clearstay.room.status_updated_successfully'),
            'room' => [
                'id' => $room->id,
                'status' => $room->status,
                'is_occupied' => $room->is_occupied,
            ],
        ]);
    }
}
