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
        $nowHour = Carbon::now()->hour;

        // 1. Time-aware greeting
        if ($nowHour >= 5 && $nowHour < 12) {
            $greetingJa = 'おはようございます';
            $greetingEn = 'Good morning';
        } elseif ($nowHour >= 12 && $nowHour < 18) {
            $greetingJa = 'こんにちは';
            $greetingEn = 'Good afternoon';
        } else {
            $greetingJa = 'こんばんは';
            $greetingEn = 'Good evening';
        }

        // 2. Operational Metrics
        $todayArrivalsCount = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_in_date', $today)
            ->where('status', 'confirmed')
            ->count();

        $todayArrivalsCheckedInCount = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_in_date', $today)
            ->where('status', 'checked_in')
            ->count();

        $todayDeparturesCount = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_out_date', $today)
            ->where('status', 'checked_in')
            ->count();

        $todayDeparturesCheckedOutCount = Reservation::where('hotel_id', $hotelId)
            ->whereDate('check_out_date', $today)
            ->where('status', 'checked_out')
            ->count();

        $dirtyRoomsCount = Room::where('hotel_id', $hotelId)
            ->where('status', 'dirty')
            ->count();

        $inspectingRoomsCount = Room::where('hotel_id', $hotelId)
            ->where('status', 'inspecting')
            ->count();

        $cleanAvailableRoomsCount = Room::where('hotel_id', $hotelId)
            ->where('status', 'clean')
            ->where('is_occupied', false)
            ->count();

        $occupiedRoomsCount = Room::where('hotel_id', $hotelId)
            ->where('is_occupied', true)
            ->count();

        $maintenanceRoomsCount = Room::where('hotel_id', $hotelId)
            ->where('status', 'maintenance')
            ->count();

        $totalRoomsCount = Room::where('hotel_id', $hotelId)->count();

        // 3. Deterministic "Next Recommended Task" Rule Engine
        $nextTask = $this->calculateNextRecommendedTask(
            $hotelId,
            $today,
            $todayArrivalsCount,
            $todayDeparturesCount,
            $dirtyRoomsCount,
            $cleanAvailableRoomsCount
        );

        // 4. Role selection tab (receptionist, housekeeping, manager, all)
        $activeRole = $request->query('role', 'all');

        return view('easy_mode.index', compact(
            'greetingJa',
            'greetingEn',
            'todayArrivalsCount',
            'todayArrivalsCheckedInCount',
            'todayDeparturesCount',
            'todayDeparturesCheckedOutCount',
            'dirtyRoomsCount',
            'inspectingRoomsCount',
            'cleanAvailableRoomsCount',
            'occupiedRoomsCount',
            'maintenanceRoomsCount',
            'totalRoomsCount',
            'nextTask',
            'activeRole'
        ));
    }

    /**
     * Deterministic Rule Engine for Next Recommended Task
     */
    protected function calculateNextRecommendedTask(
        int $hotelId,
        string $today,
        int $todayArrivalsCount,
        int $todayDeparturesCount,
        int $dirtyRoomsCount,
        int $cleanAvailableRoomsCount
    ): array {
        // Priority 1: Incoming arrivals waiting while rooms need cleaning
        if ($todayArrivalsCount > 0 && $dirtyRoomsCount > 0 && $cleanAvailableRoomsCount < $todayArrivalsCount) {
            $firstArrival = Reservation::with(['guest', 'room', 'room.roomType'])
                ->where('hotel_id', $hotelId)
                ->whereDate('check_in_date', $today)
                ->where('status', 'confirmed')
                ->first();

            $guestName = $firstArrival && $firstArrival->guest
                ? "{$firstArrival->guest->last_name} {$firstArrival->guest->first_name}"
                : 'お客様';

            return [
                'type' => 'cleaning',
                'priority' => 'urgent',
                'badge_ja' => '最優先タスク',
                'badge_en' => 'Highest Priority',
                'icon' => 'fa-solid fa-broom',
                'color' => '#D97706',
                'title_ja' => "本日到着（残り{$todayArrivalsCount}組）のため客室清掃を優先してください",
                'title_en' => "Prioritize room cleaning for {$todayArrivalsCount} incoming arrivals today",
                'desc_ja' => "{$guestName} 様をはじめとする本日チェックインのお客様に向けて、清掃待ちのお部屋（{$dirtyRoomsCount}室）の仕上げと点検を完了してください。",
                'desc_en' => "Prepare clean rooms for arriving guests. Currently {$dirtyRoomsCount} rooms require turnover cleaning.",
                'action_url' => route('easy_mode.rooms'),
                'action_label_ja' => '清掃待ちの部屋を確認する',
                'action_label_en' => 'View Cleaning Queue',
            ];
        }

        // Priority 2: Arrivals waiting to check in (ready rooms available)
        if ($todayArrivalsCount > 0) {
            $nextArrival = Reservation::with(['guest', 'room', 'room.roomType'])
                ->where('hotel_id', $hotelId)
                ->whereDate('check_in_date', $today)
                ->where('status', 'confirmed')
                ->orderBy('id', 'asc')
                ->first();

            $guestName = $nextArrival && $nextArrival->guest
                ? "{$nextArrival->guest->last_name} {$nextArrival->guest->first_name} 様"
                : '到着予定のゲスト';

            $roomText = $nextArrival && $nextArrival->room
                ? "お部屋番号 {$nextArrival->room->room_number}"
                : '部屋割当可';

            return [
                'type' => 'checkin',
                'priority' => 'high',
                'badge_ja' => 'チェックイン受付',
                'badge_en' => 'Check-in Waiting',
                'icon' => 'fa-solid fa-door-open',
                'color' => '#059669',
                'title_ja' => "{$guestName} のチェックイン受付が可能です ({$roomText})",
                'title_en' => "Guest check-in ready for {$guestName} ({$roomText})",
                'desc_ja' => "5ステップ・かんたんチェックイン案内を使用して、鍵の受渡と本人確認をスムーズに完了させてください。",
                'desc_en' => "Proceed to 5-Step Guided Check-In Wizard for fast, error-free room assignment and keycard issuance.",
                'action_url' => route('easy_mode.checkin_wizard', ['reservation_id' => $nextArrival ? $nextArrival->id : null]),
                'action_label_ja' => 'チェックインを開始する',
                'action_label_en' => 'Start 5-Step Check-In',
            ];
        }

        // Priority 3: Pending departures
        if ($todayDeparturesCount > 0) {
            return [
                'type' => 'checkout',
                'priority' => 'standard',
                'badge_ja' => 'チェックアウト確認',
                'badge_en' => 'Departures Pending',
                'icon' => 'fa-solid fa-person-walking-dashed-line-arrow-right',
                'color' => '#2563EB',
                'title_ja' => "本日出発予定のゲスト様（残り{$todayDeparturesCount}組）のチェックアウト",
                'title_en' => "Process checkouts for {$todayDeparturesCount} departing guests today",
                'desc_ja' => "鍵の回収とチェックアウト確定を行ってください。チェックアウト完了と同時に客室が清掃待ち（Dirty）に自動更新されます。",
                'desc_en' => "Review departures and execute 1-touch checkout. The system will automatically mark the room dirty and generate a cleaning task.",
                'action_url' => route('easy_mode.departures'),
                'action_label_ja' => '出発一覧・チェックアウトへ',
                'action_label_en' => 'Go to Departures List',
            ];
        }

        // Priority 4: Dirty rooms requiring cleaning (no immediate arrivals)
        if ($dirtyRoomsCount > 0) {
            return [
                'type' => 'cleaning',
                'priority' => 'routine',
                'badge_ja' => '客室清掃',
                'badge_en' => 'Housekeeping',
                'icon' => 'fa-solid fa-broom',
                'color' => '#D97706',
                'title_ja' => "現在 {$dirtyRoomsCount}室 の清掃・点検が必要です",
                'title_en' => "{$dirtyRoomsCount} rooms awaiting cleaning and inspection",
                'desc_ja' => "清掃完了後にボタンをタッチして「清掃完了 (Clean)」に切り替えると、次の宿泊予約へ自動で割り当て可能になります。",
                'desc_en' => "Turn over dirty rooms to clean status with 1-click status toggles to restore available inventory.",
                'action_url' => route('easy_mode.rooms'),
                'action_label_ja' => '客室の清掃状態を見る',
                'action_label_en' => 'Open Housekeeping Grid',
            ];
        }

        // Priority 5: All operational tasks complete
        return [
            'type' => 'ready',
            'priority' => 'complete',
            'badge_ja' => '全業務順調',
            'badge_en' => 'All Clear',
            'icon' => 'fa-solid fa-circle-check',
            'color' => '#047857',
            'title_ja' => '本日の主要な到着・出発・清掃タスクは完了しています',
            'title_en' => 'All daily arrivals, departures, and cleaning tasks are up to date',
            'desc_ja' => 'システムは安全に稼働中です。新しいウォークイン予約の登録やお部屋状態の確認をいつでも行えます。',
            'desc_en' => 'Operations are running smoothly. You can create walk-in reservations or monitor room occupancy at any time.',
            'action_url' => route('easy_mode.create'),
            'action_label_ja' => '新しい予約を登録する',
            'action_label_en' => 'Create New Reservation',
        ];
    }

    /**
     * Guided Operation 1: Today's Arrivals & 1-Click Check-In.
     */
    public function arrivals(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $today = Carbon::today()->toDateString();

        $arrivals = Reservation::with(['guest', 'room', 'room.roomType', 'channel'])
            ->where('hotel_id', $hotelId)
            ->whereDate('check_in_date', $today)
            ->where('status', 'confirmed')
            ->orderBy('id', 'asc')
            ->get();

        $checkedInToday = Reservation::with(['guest', 'room', 'room.roomType', 'channel'])
            ->where('hotel_id', $hotelId)
            ->whereDate('check_in_date', $today)
            ->where('status', 'checked_in')
            ->orderBy('checked_in_at', 'desc')
            ->get();

        return view('easy_mode.arrivals', compact('arrivals', 'checkedInToday'));
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

        $departures = Reservation::with(['guest', 'room', 'room.roomType', 'channel'])
            ->where('hotel_id', $hotelId)
            ->where('status', 'checked_in')
            ->orderBy('check_out_date', 'asc')
            ->get();

        $checkedOutToday = Reservation::with(['guest', 'room', 'room.roomType', 'channel'])
            ->where('hotel_id', $hotelId)
            ->whereDate('check_out_date', $today)
            ->where('status', 'checked_out')
            ->orderBy('checked_out_at', 'desc')
            ->get();

        return view('easy_mode.departures', compact('departures', 'checkedOutToday'));
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
                        'task_type' => 'checkout_turnover',
                        'priority' => 'high',
                        'status' => 'pending',
                        'notes' => 'Checkout cleaning generated via Easy Mode (かんたん操作)',
                    ]);

                    // Record in Inventory Ledger if available
                    if (class_exists(\App\Models\InventoryLedgerEntry::class)) {
                        \App\Models\InventoryLedgerEntry::create([
                            'hotel_id' => $hotelId,
                            'room_type_id' => $room->room_type_id,
                            'room_id' => $room->id,
                            'reservation_id' => $reservation->id,
                            'stay_date' => now()->toDateString(),
                            'previous_availability' => 0,
                            'new_availability' => 0,
                            'delta' => 0,
                            'operation_type' => 'CHECKOUT_TURNOVER',
                            'source' => 'EASY_MODE_CHECKOUT',
                            'actor_type' => 'USER',
                            'actor_user_id' => auth()->id() ?? 1,
                            'correlation_id' => 'EZ-OUT-' . $reservation->id . '-' . time(),
                            'idempotency_key' => 'idemp-ezcheckout-' . $reservation->id . '-' . now()->toDateString(),
                            'notes' => "Check-out completed via Easy Mode for {$reservation->confirmation_code} (Room {$room->room_number})",
                        ]);
                    }
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
        $statusFilter = $request->query('status');

        $query = Room::with('roomType')
            ->where('hotel_id', $hotelId);

        if ($statusFilter && in_array($statusFilter, ['clean', 'dirty', 'inspecting', 'maintenance'])) {
            $query->where('status', $statusFilter);
        }

        $rooms = $query->orderBy('room_number')->get();

        $counts = [
            'all' => Room::where('hotel_id', $hotelId)->count(),
            'dirty' => Room::where('hotel_id', $hotelId)->where('status', 'dirty')->count(),
            'inspecting' => Room::where('hotel_id', $hotelId)->where('status', 'inspecting')->count(),
            'clean' => Room::where('hotel_id', $hotelId)->where('status', 'clean')->count(),
            'maintenance' => Room::where('hotel_id', $hotelId)->where('status', 'maintenance')->count(),
        ];

        return view('easy_mode.rooms', compact('rooms', 'counts', 'statusFilter'));
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

        $oldStatus = $room->status;
        $room->status = $request->input('status');
        $room->save();

        // If marked clean, update any pending housekeeping task to completed
        if ($room->status === 'clean') {
            HousekeepingTask::where('hotel_id', $hotelId)
                ->where('room_id', $room->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->update(['status' => 'completed']);
        }

        $statusLabels = [
            'clean' => '清掃完了・入室可能 (Clean)',
            'dirty' => '清掃待ち (Dirty)',
            'inspecting' => '点検中 (Inspecting)',
            'maintenance' => '修繕・メンテナンス中 (Maintenance)',
        ];

        $newLabel = $statusLabels[$room->status] ?? strtoupper($room->status);

        return redirect()->route('easy_mode.rooms')
            ->with('success', "部屋 {$room->room_number} の状態を「{$newLabel}」に更新しました / Room status updated.");
    }

    /**
     * Guided Operation 5: Simplified Reservation Lookup.
     */
    public function reservations(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $search = trim($request->input('q', ''));

        $query = Reservation::with(['guest', 'room', 'room.roomType', 'channel'])
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

        $reservations = $query->orderBy('check_in_date', 'desc')->take(25)->get();

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
            ->where('status', 'clean')
            ->where('is_occupied', false)
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
            'room_type_id' => $room->room_type_id,
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
            'special_requests' => 'Created via Easy Mode (かんたん操作)',
        ]);

        return redirect()->route('easy_mode.reservations')
            ->with('success', "かんたんモードで予約を登録しました / Reservation {$confirmationCode} created successfully in Easy Mode!");
    }

    /**
     * Guided Operation 7: Dedicated 5-Step Receptionist Check-In Wizard.
     */
    public function checkinWizard(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $today = Carbon::today()->toDateString();

        $selectedId = $request->query('reservation_id');
        $selectedReservation = null;
        if ($selectedId) {
            $selectedReservation = Reservation::with(['guest', 'room', 'room.roomType', 'channel'])
                ->where('hotel_id', $hotelId)
                ->find($selectedId);
        }

        $todayArrivals = Reservation::with(['guest', 'room', 'room.roomType', 'channel'])
            ->where('hotel_id', $hotelId)
            ->whereDate('check_in_date', $today)
            ->where('status', 'confirmed')
            ->orderBy('id', 'desc')
            ->take(15)
            ->get();

        $availableRooms = Room::with('roomType')
            ->where('hotel_id', $hotelId)
            ->where('status', 'clean')
            ->where('is_occupied', false)
            ->orderBy('room_number')
            ->get();

        return view('easy_mode.checkin_wizard', compact(
            'selectedReservation',
            'todayArrivals',
            'availableRooms',
            'today'
        ));
    }

    /**
     * JSON search endpoint for Check-In Wizard live lookup.
     */
    public function searchReservationApi(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $q = trim($request->input('q', ''));

        if (empty($q)) {
            return response()->json(['results' => []]);
        }

        $results = Reservation::with(['guest', 'room', 'room.roomType'])
            ->where('hotel_id', $hotelId)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where(function ($sub) use ($q) {
                $sub->where('confirmation_code', 'LIKE', "%{$q}%")
                    ->orWhereHas('guest', function ($gq) use ($q) {
                        $gq->where('first_name', 'LIKE', "%{$q}%")
                           ->orWhere('last_name', 'LIKE', "%{$q}%")
                           ->orWhere('phone', 'LIKE', "%{$q}%")
                           ->orWhere('passport_or_id', 'LIKE', "%{$q}%");
                    });
            })
            ->take(10)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'confirmation_code' => $r->confirmation_code,
                    'guest_name' => $r->guest ? "{$r->guest->first_name} {$r->guest->last_name}" : 'Unknown Guest',
                    'phone' => $r->guest->phone ?? '—',
                    'passport_or_id' => $r->guest->passport_or_id ?? '—',
                    'room_number' => $r->room->room_number ?? 'Unassigned',
                    'room_id' => $r->room_id,
                    'room_type' => $r->room->roomType->name ?? 'Standard',
                    'check_in_date' => $r->check_in_date ? $r->check_in_date->format('Y-m-d') : '',
                    'check_out_date' => $r->check_out_date ? $r->check_out_date->format('Y-m-d') : '',
                    'nights' => $r->nights ?? 1,
                    'total_price' => number_format((float)$r->total_price),
                    'status' => $r->status,
                ];
            });

        return response()->json(['results' => $results]);
    }

    /**
     * Complete the Check-In Wizard transaction.
     */
    public function completeCheckinWizard(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'room_id' => 'required|exists:rooms,id',
            'keycards_issued' => 'nullable|integer|min:1|max:4',
            'passport_or_id' => 'nullable|string|max:50',
            'guest_phone' => 'nullable|string|max:30',
        ]);

        $hotelId = session('current_hotel_id') ?? auth()->user()->hotel_id;
        $reservationId = $request->input('reservation_id');
        $roomId = $request->input('room_id');
        $keycards = $request->input('keycards_issued', 1);

        $result = DB::transaction(function () use ($hotelId, $reservationId, $roomId, $keycards, $request) {
            $reservation = Reservation::with('guest')
                ->where('hotel_id', $hotelId)
                ->lockForUpdate()
                ->findOrFail($reservationId);

            if ($reservation->status === 'checked_in') {
                return [
                    'success' => true,
                    'already_checked_in' => true,
                    'reservation' => $reservation,
                    'message' => 'This reservation is already checked in.',
                ];
            }

            $room = Room::where('hotel_id', $hotelId)
                ->lockForUpdate()
                ->findOrFail($roomId);

            // Update guest identity if provided
            if ($reservation->guest) {
                if ($request->filled('passport_or_id')) {
                    $reservation->guest->passport_or_id = $request->input('passport_or_id');
                }
                if ($request->filled('guest_phone')) {
                    $reservation->guest->phone = $request->input('guest_phone');
                }
                $reservation->guest->save();
            }

            // Update reservation
            $reservation->room_id = $room->id;
            $reservation->status = 'checked_in';
            $reservation->checked_in_at = now();
            $specialNotes = $reservation->special_requests ?: '';
            $reservation->special_requests = trim($specialNotes . " [Easy Mode Check-In: {$keycards} keycard(s) issued at " . now()->format('H:i') . ']');
            $reservation->save();

            // Mark room occupied
            $room->is_occupied = true;
            $room->save();

            // Record in Inventory Ledger if available
            if (class_exists(\App\Models\InventoryLedgerEntry::class)) {
                \App\Models\InventoryLedgerEntry::create([
                    'hotel_id' => $hotelId,
                    'room_type_id' => $room->room_type_id,
                    'room_id' => $room->id,
                    'reservation_id' => $reservation->id,
                    'stay_date' => $reservation->check_in_date ?? now()->toDateString(),
                    'previous_availability' => 1,
                    'new_availability' => 0,
                    'delta' => -1,
                    'operation_type' => 'CHECK_IN_OCCUPIED',
                    'source' => 'EASY_MODE_WIZARD',
                    'actor_type' => 'USER',
                    'actor_user_id' => auth()->id() ?? 1,
                    'correlation_id' => 'EZ-WIZ-' . $reservation->id . '-' . time(),
                    'idempotency_key' => 'idemp-ezcheckin-' . $reservation->id . '-' . now()->toDateString(),
                    'notes' => "Check-in completed via 5-step Easy Mode Wizard for {$reservation->confirmation_code} (Room {$room->room_number}, {$keycards} keys issued)",
                ]);
            }

            return [
                'success' => true,
                'already_checked_in' => false,
                'reservation' => $reservation->fresh(['guest', 'room', 'room.roomType']),
                'message' => 'Check-in completed successfully!',
            ];
        });

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->route('easy_mode.checkin_wizard', ['reservation_id' => $reservationId])
            ->with('success', "チェックイン完了！ 部屋番号 {$result['reservation']->room->room_number} の鍵 ({$keycards}枚) をお渡しください / Check-in complete! Hand over {$keycards} room key(s).");
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
                ->with('success', 'かんたん操作モードが有効になりました / Easy Mode enabled (Large Text, High Contrast, Guided Workflows).');
        } else {
            return redirect()->route('overview.index')
                ->with('info', '標準プロフェッショナルモードに戻りました / Returned to Standard Professional View.');
        }
    }
}
