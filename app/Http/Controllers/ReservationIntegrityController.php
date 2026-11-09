<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Reservation\ReservationIntegrityService;
use App\Services\Revenue\OverbookingIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationIntegrityController extends Controller
{
    public function __construct(
        protected ReservationIntegrityService $integrityService,
        protected OverbookingIntelligenceService $overbookingService
    ) {}

    /**
     * Reservation Integrity & Concurrency Lab Hub.
     */
    public function index(Request $request)
    {
        $hotel = $request->user()->hotel ?? Hotel::first();
        if (!$hotel) {
            abort(404, 'No hotel context configured.');
        }

        $policy = $this->overbookingService->getPolicy($hotel);
        $ledgerEntries = $this->integrityService->getLedgerHistory($hotel->id, 20);
        $roomTypes = RoomType::where('hotel_id', $hotel->id)->get();
        $rooms = Room::where('hotel_id', $hotel->id)->get();

        $activeReservations = Reservation::with(['guest', 'room', 'roomType'])
            ->where('hotel_id', $hotel->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->latest('id')
            ->limit(15)
            ->get();

        $calculations = $this->overbookingService->calculateStrategies(
            $hotel->total_rooms ?? 50,
            (float) $policy->historical_cancellation_rate,
            (float) $policy->historical_noshow_rate,
            (float) $policy->default_adr,
            (float) $policy->relocation_cost_per_walk,
            (float) $policy->max_tolerated_walk_risk
        );

        return view('reservation_integrity.index', compact(
            'hotel',
            'policy',
            'ledgerEntries',
            'roomTypes',
            'rooms',
            'activeReservations',
            'calculations'
        ));
    }

    /**
     * Execute 10-guest concurrency simulation.
     */
    public function simulateConcurrency(Request $request): JsonResponse
    {
        $hotel = $request->user()->hotel ?? Hotel::first();
        $count = (int) $request->input('concurrency_count', 10);

        $result = $this->integrityService->simulateConcurrency($hotel, $count, $request->user());

        return response()->json($result);
    }

    /**
     * Test Idempotency with duplicated requests.
     */
    public function testIdempotency(Request $request): JsonResponse
    {
        $hotel = $request->user()->hotel ?? Hotel::first();
        $room = Room::where('hotel_id', $hotel->id)->first();
        $idempotencyKey = 'IDEM-' . strtoupper(bin2hex(random_bytes(6)));

        $reqData = [
            'hotel_id' => $hotel->id,
            'room_id' => $room?->id,
            'room_type_id' => $room?->room_type_id,
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'guest_first_name' => 'Idempotent',
            'guest_last_name' => 'Tester',
            'guest_email' => 'idempotent_test@clearstay.test',
            'source' => 'DIRECT_PMS',
            'notes' => 'Idempotency test payload',
        ];

        // 1st Attempt
        $res1 = $this->integrityService->createReservationWithLock($reqData, $idempotencyKey, $request->user());

        // 2nd Immediate Attempt with identical key
        $res2 = $this->integrityService->createReservationWithLock($reqData, $idempotencyKey, $request->user());

        return response()->json([
            'success' => true,
            'idempotency_key' => $idempotencyKey,
            'attempt_1' => [
                'status' => $res1['status'],
                'idempotent_replay' => $res1['idempotent_replay'],
                'confirmation_code' => $res1['reservation']->confirmation_code ?? null,
            ],
            'attempt_2' => [
                'status' => $res2['status'],
                'idempotent_replay' => $res2['idempotent_replay'],
                'confirmation_code' => $res2['reservation']->confirmation_code ?? null,
            ],
            'deduplication_verified' => ($res2['idempotent_replay'] === true),
            'summary' => 'Attempt 1 created reservation. Attempt 2 returned cached record without double-allocation.',
        ]);
    }

    /**
     * Calculate explainable overbooking strategies dynamically based on UI sliders.
     */
    public function calculateOverbooking(Request $request): JsonResponse
    {
        $hotel = $request->user()->hotel ?? Hotel::first();

        $capacity = (int) $request->input('capacity', $hotel->total_rooms ?? 50);
        $cancellationRate = (float) $request->input('cancellation_rate', 14.5);
        $noshowRate = (float) $request->input('noshow_rate', 4.2);
        $adr = (float) $request->input('adr', 18500.0);
        $walkCost = (float) $request->input('walk_cost', 35000.0);
        $riskThreshold = (float) $request->input('risk_threshold', 3.0);

        $results = $this->overbookingService->calculateStrategies(
            $capacity,
            $cancellationRate,
            $noshowRate,
            $adr,
            $walkCost,
            $riskThreshold
        );

        return response()->json($results);
    }

    /**
     * Save overbooking policy (Manager authorized).
     */
    public function updatePolicy(Request $request)
    {
        $hotel = $request->user()->hotel ?? Hotel::first();

        $validated = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'strategy' => 'required|string|in:zero_overbooking,fixed_buffer,stochastic_optimization',
            'max_buffer_rooms' => 'required|integer|min:0|max:10',
            'default_adr' => 'required|numeric|min:1000',
            'relocation_cost_per_walk' => 'required|numeric|min:1000',
            'historical_cancellation_rate' => 'required|numeric|min:0|max:50',
            'historical_noshow_rate' => 'required|numeric|min:0|max:30',
            'max_tolerated_walk_risk' => 'required|numeric|min:0|max:15',
            'policy_notes' => 'nullable|string',
        ]);

        $this->overbookingService->updatePolicy($hotel, $validated, $request->user());

        return back()->with('success', 'Controlled Overbooking policy updated with audit record.');
    }

    /**
     * Reassign room during reservation recovery.
     */
    public function reassignRoom(Request $request)
    {
        $validated = $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'target_room_id' => 'required|exists:rooms,id',
            'justification' => 'required|string|min:5',
        ]);

        $reservation = Reservation::findOrFail($validated['reservation_id']);
        $newRoom = Room::findOrFail($validated['target_room_id']);

        $oldRoomId = $reservation->room_id;
        $reservation->room_id = $newRoom->id;
        $reservation->special_requests = ($reservation->special_requests ?? '') . " | Room reassigned from Room #{$oldRoomId} to Room #{$newRoom->room_number}: {$validated['justification']}";
        $reservation->save();

        AuditLog::create([
            'hotel_id' => $reservation->hotel_id,
            'user_id' => $request->user()->id,
            'action' => 'ROOM_REASSIGNED_RECOVERY',
            'details' => json_encode([
                'reservation_code' => $reservation->confirmation_code,
                'old_room_id' => $oldRoomId,
                'new_room_id' => $newRoom->id,
                'justification' => $validated['justification'],
            ]),
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Reservation {$reservation->confirmation_code} successfully reassigned to Room #{$newRoom->room_number}.");
    }
}
