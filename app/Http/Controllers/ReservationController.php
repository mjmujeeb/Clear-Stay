<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Reservations list.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId);

        $status = $request->input('status');
        $search = $request->input('search');
        $date = $request->input('date');

        $query = Reservation::with(['guest', 'room.roomType'])
            ->where('hotel_id', $hotelId);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('confirmation_code', 'like', "%{$search}%")
                    ->orWhereHas('guest', function ($g) use ($search) {
                        $g->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('room', function ($r) use ($search) {
                        $r->where('room_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($date) {
            $query->where(function ($q) use ($date) {
                $q->whereDate('check_in_date', '<=', $date)
                    ->whereDate('check_out_date', '>', $date);
            });
        }

        $reservations = $query->orderBy('check_in_date', 'desc')->paginate(15)->withQueryString();

        $rooms = Room::with('roomType')->where('hotel_id', $hotelId)->get();

        return view('reservations.index', compact('hotel', 'reservations', 'status', 'search', 'date', 'rooms'));
    }

    /**
     * Create reservation form.
     */
    public function create()
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId);
        $rooms = Room::with('roomType')->where('hotel_id', $hotelId)->get();
        $guests = Guest::where('hotel_id', $hotelId)->orderBy('last_name')->get();

        return view('reservations.create', compact('hotel', 'rooms', 'guests'));
    }

    /**
     * Store reservation.
     */
    public function store(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $request->validate([
            'guest_mode' => 'required|in:existing,new',
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'adults' => 'required|integer|min:1|max:10',
            'children' => 'nullable|integer|min:0|max:10',
            'special_requests' => 'nullable|string|max:500',
            // If new guest
            'first_name' => 'required_if:guest_mode,new|nullable|string|max:100',
            'last_name' => 'required_if:guest_mode,new|nullable|string|max:100',
            'email' => 'required_if:guest_mode,new|nullable|email|max:150',
            'phone' => 'nullable|string|max:30',
            'guest_id' => 'required_if:guest_mode,existing|nullable|exists:guests,id',
        ]);

        if ($request->guest_mode === 'new') {
            $guest = Guest::firstOrCreate([
                'hotel_id' => $hotelId,
                'email' => $request->email,
            ], [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
            ]);
            $guestId = $guest->id;
        } else {
            $guestId = $request->guest_id;
        }

        try {
            $reservation = $this->reservationService->createReservation([
                'hotel_id' => $hotelId,
                'guest_id' => $guestId,
                'room_id' => $request->room_id,
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'adults' => $request->adults,
                'children' => $request->children ?? 0,
                'special_requests' => $request->special_requests,
                'source' => 'direct',
            ], Auth::id());

            return redirect()->route('reservations.index')
                ->with('success', __('clearstay.reservations.created_successfully', ['code' => $reservation->confirmation_code]));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Show reservation modal detail for jQuery pop-up preview.
     */
    public function show(int $id): JsonResponse
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $reservation = Reservation::withoutGlobalScopes()
            ->with(['guest', 'room.roomType', 'changes.user', 'changes.oldRoom', 'changes.newRoom'])
            ->where('hotel_id', $hotelId)
            ->findOrFail($id);

        $availableRooms = Room::with('roomType')
            ->where('hotel_id', $hotelId)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'number' => $r->room_number,
                'type' => $r->roomType->name,
                'price' => $r->roomType->base_price,
            ]);

        return response()->json([
            'success' => true,
            'reservation' => [
                'id' => $reservation->id,
                'confirmation_code' => $reservation->confirmation_code,
                'guest_name' => $reservation->guest->full_name,
                'guest_email' => $reservation->guest->email,
                'guest_phone' => $reservation->guest->phone ?? 'N/A',
                'vip_level' => $reservation->guest->vip_level,
                'room_id' => $reservation->room_id,
                'room_number' => $reservation->room->room_number,
                'room_type' => $reservation->room->roomType->name,
                'check_in_date' => $reservation->check_in_date->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date->format('Y-m-d'),
                'nights' => $reservation->nights,
                'adults' => $reservation->adults,
                'children' => $reservation->children,
                'total_price' => number_format($reservation->total_price),
                'payment_status' => $reservation->payment_status,
                'status' => $reservation->status,
                'special_requests' => $reservation->special_requests ?? 'None',
                'guest_portal_url' => route('guest.portal', ['token' => $reservation->guest_portal_token]),
                'changes' => $reservation->changes->map(fn ($c) => [
                    'date' => $c->created_at->format('Y-m-d H:i'),
                    'user' => $c->user ? $c->user->name : 'System',
                    'old_dates' => "{$c->old_check_in_date->format('Y-m-d')} ~ {$c->old_check_out_date->format('Y-m-d')}",
                    'new_dates' => "{$c->new_check_in_date->format('Y-m-d')} ~ {$c->new_check_out_date->format('Y-m-d')}",
                    'reason' => $c->reason,
                ]),
            ],
            'available_rooms' => $availableRooms,
        ]);
    }

    /**
     * Change reservation dates using Row-Locked Database Transaction.
     */
    public function changeDates(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'room_id' => 'nullable|exists:rooms,id',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $updated = $this->reservationService->changeDatesWithRowLock(
                $id,
                $request->check_in_date,
                $request->check_out_date,
                $request->room_id ? intval($request->room_id) : null,
                $request->reason,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => __('clearstay.reservations.date_change_success', ['code' => $updated->confirmation_code]),
                'reservation' => [
                    'id' => $updated->id,
                    'check_in' => $updated->check_in_date->format('Y-m-d'),
                    'check_out' => $updated->check_out_date->format('Y-m-d'),
                    'total_price' => number_format($updated->total_price),
                    'room_number' => $updated->room->room_number,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check in guest.
     */
    public function checkIn(int $id)
    {
        $this->reservationService->checkIn($id);
        return back()->with('success', __('clearstay.reservations.checked_in_success'));
    }

    /**
     * Check out guest.
     */
    public function checkOut(int $id)
    {
        $this->reservationService->checkOut($id);
        return back()->with('success', __('clearstay.reservations.checked_out_success'));
    }

    /**
     * Cancel reservation.
     */
    public function cancel(Request $request, int $id)
    {
        $this->reservationService->cancel($id, $request->input('reason'));
        return back()->with('info', __('clearstay.reservations.cancelled_success'));
    }
}
