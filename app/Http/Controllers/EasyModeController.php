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
     * Guided Operation 6: Easy Mode Create New Reservation Form.
     */
    public function create(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $rooms = Room::with('roomType')
            ->where('hotel_id', $hotelId)
            ->orderBy('room_number')
            ->get();

        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();

        return view('easy_mode.create', compact('rooms', 'today', 'tomorrow'));
    }

    /**
     * Store new reservation created via Easy Mode.
     */
    public function store(Request $request)
    {
        $request->validate([
            'guest_name' => 'required|string|min:2|max:100',
            'guest_phone' => 'nullable|string|max:30',
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $room = Room::with('roomType')->where('hotel_id', $hotelId)->findOrFail($request->input('room_id'));

        $checkIn = Carbon::parse($request->input('check_in_date'));
        $checkOut = Carbon::parse($request->input('check_out_date'));
        $nights = max(1, $checkIn->diffInDays($checkOut));

        $nameParts = explode(' ', trim($request->input('guest_name')), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? 'Guest';

        $guest = Guest::firstOrCreate(
            [
                'hotel_id' => $hotelId,
                'email' => strtolower($firstName . '.' . $lastName . '@guest.clearstay.jp'),
            ],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $request->input('guest_phone') ?: '+81-90-0000-' . rand(1000, 9999),
            ]
        );

        $pricePerNight = $room->roomType->base_price ?? 15000;
        $totalPrice = $pricePerNight * $nights;
        $confirmationCode = 'CS-EZ-' . strtoupper(\Illuminate\Support\Str::random(6));

        $res = Reservation::create([
            'hotel_id' => $hotelId,
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'confirmation_code' => $confirmationCode,
            'check_in_date' => $checkIn->toDateString(),
            'check_out_date' => $checkOut->toDateString(),
            'nights' => $nights,
            'adults' => 1,
            'children' => 0,
            'total_price' => $totalPrice,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'guest_portal_token' => \Illuminate\Support\Str::random(32),
            'notes' => 'Created via Easy Mode (かんたん操作)',
        ]);

        return redirect()->route('easy_mode.reservations')
            ->with('success', "かんたんモードで予約を登録しました / Reservation {$confirmationCode} created successfully in Easy Mode!");
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
