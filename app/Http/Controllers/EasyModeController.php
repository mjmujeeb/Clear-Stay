<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EasyModeController extends Controller
{
    /**
     * Easy Mode Main Hub — Simplified Hotel Operations.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $today = Carbon::today()->toDateString();

        $todayArrivalsCount = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_in_date', $today)
            ->where('status', 'confirmed')
            ->count();

        $todayDeparturesCount = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_out_date', $today)
            ->where('status', 'checked_in')
            ->count();

        $dirtyRoomsCount = Room::where('hotel_id', $hotelId)
            ->where('status', 'dirty')
            ->count();

        $cleanAvailableRoomsCount = Room::where('hotel_id', $hotelId)
            ->where('status', 'clean')
            ->where('is_occupied', false)
            ->count();

        return view('easy_mode.index', compact(
            'todayArrivalsCount',
            'todayDeparturesCount',
            'dirtyRoomsCount',
            'cleanAvailableRoomsCount'
        ));
    }

    /**
     * Guided Operation 1: Today's Arrivals & 1-Click Check-In.
     */
    public function arrivals(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $today = Carbon::today()->toDateString();

        $arrivals = Reservation::with(['guest', 'room', 'room.roomType'])
            ->where('hotel_id', $hotelId)
            ->whereDate('check_in_date', $today)
            ->where('status', 'confirmed')
            ->orderBy('check_in_date')
            ->get();

        return view('easy_mode.arrivals', compact('arrivals'));
    }

    /**
     * Guided Operation 2: Safe Check-In Transaction.
     */
    public function checkIn(Request $request, $id)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;

        DB::transaction(function () use ($id, $hotelId) {
            $reservation = Reservation::where('hotel_id', $hotelId)
                ->lockForUpdate()
                ->findOrFail($id);

            $reservation->status = 'checked_in';
            $reservation->checked_in_at = now();
            $reservation->save();

            if ($reservation->room_id) {
                $room = Room::where('hotel_id', $hotelId)
                    ->lockForUpdate()
                    ->find($reservation->room_id);

                if ($room) {
                    $room->is_occupied = true;
                    $room->save();
                }
            }
        });

        return redirect()->route('easy_mode.arrivals')
            ->with('success', 'チェックインが完了しました / Check-in completed successfully! The room is now occupied.');
    }

    /**
     * Guided Operation 3: Today's Departures & 1-Click Check-Out.
     */
    public function departures(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $today = Carbon::today()->toDateString();

        $departures = Reservation::with(['guest', 'room', 'room.roomType'])
            ->where('hotel_id', $hotelId)
            ->where('status', 'checked_in')
            ->orderBy('check_out_date')
            ->get();

        return view('easy_mode.departures', compact('departures'));
    }

    /**
     * Guided Operation 3 (Action): Safe Check-Out Transaction with Auto Housekeeping.
     */
    public function checkOut(Request $request, $id)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;

        DB::transaction(function () use ($id, $hotelId) {
            $reservation = Reservation::where('hotel_id', $hotelId)
                ->lockForUpdate()
                ->findOrFail($id);

            $reservation->status = 'checked_out';
            $reservation->checked_out_at = now();
            $reservation->save();

            if ($reservation->room_id) {
                $room = Room::where('hotel_id', $hotelId)
                    ->lockForUpdate()
                    ->find($reservation->room_id);

                if ($room) {
                    $room->is_occupied = false;
                    $room->status = 'dirty';
                    $room->save();

                    // Generate Housekeeping Task
                    HousekeepingTask::create([
                        'hotel_id' => $hotelId,
                        'room_id' => $room->id,
                        'type' => 'turnover',
                        'priority' => 'high',
                        'status' => 'pending',
                        'notes' => 'Checkout cleaning generated via Easy Mode',
                    ]);
                }
            }
        });

        return redirect()->route('easy_mode.departures')
            ->with('success', 'チェックアウトが完了しました / Check-out complete! Room marked dirty and cleaning task scheduled.');
    }

    /**
     * Guided Operation 4: Big Visual Room Status Tiles & Clean/Dirty Toggle.
     */
    public function rooms(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;

        $rooms = Room::with('roomType')
            ->where('hotel_id', $hotelId)
            ->orderBy('room_number')
            ->get();

        return view('easy_mode.rooms', compact('rooms'));
    }

    /**
     * Update room status from Easy Mode tile.
     */
    public function updateRoomStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:clean,dirty,inspecting,maintenance',
        ]);

        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $room = Room::where('hotel_id', $hotelId)->findOrFail($id);

        $room->status = $request->input('status');
        $room->save();

        return redirect()->route('easy_mode.rooms')
            ->with('success', "部屋 {$room->room_number} の状態を " . strtoupper($room->status) . " に更新しました / Room status updated.");
    }

    /**
     * Guided Operation 5: Simplified Reservation Lookup.
     */
    public function reservations(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $search = trim($request->input('q', ''));

        $query = Reservation::with(['guest', 'room', 'room.roomType'])
            ->where('hotel_id', $hotelId);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('confirmation_code', 'LIKE', "%{$search}%")
                  ->orWhereHas('guest', function ($gq) use ($search) {
                      $gq->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%")
                         ->orWhere('phone', 'LIKE', "%{$search}%");
                  });
            });
        }

        $reservations = $query->orderBy('check_in_date', 'desc')->take(20)->get();

        return view('easy_mode.reservations', compact('reservations', 'search'));
    }

    /**
     * Toggle Easy Mode Session State.
     */
    public function toggle(Request $request)
    {
        $current = session('clearstay_easy_mode', false);
        $new = !$current;
        session(['clearstay_easy_mode' => $new]);

        if ($new) {
            return redirect()->route('easy_mode.index')
                ->with('success', 'かんたんモードが有効になりました / Easy Mode enabled (Large Text, High Contrast, Guided Workflows).');
        } else {
            return redirect()->route('overview.index')
                ->with('info', '標準プロフェッショナルモードに戻りました / Returned to Standard Professional View.');
        }
    }
}
