<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\SimulatedFailureConfig;
use App\Models\SimulatedPartnerInventory;
use App\Models\SimulatedPartnerProperty;
use App\Models\SimulatedPartnerRequest;
use App\Models\SimulatedPartnerReservation;
use App\Models\SyncAttemptLog;
use App\Models\SyncOutboxEvent;
use App\Services\Channel\InboundBookingWebhookService;
use App\Services\Channel\SimulatedRemoteStateService;
use App\Services\Integration\IntegrationModeService;
use App\Services\Integration\InterviewerSimulationScenarioService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class IntegrationSimulatorController extends Controller
{
    public function __construct(
        protected IntegrationModeService $modeService,
        protected SimulatedRemoteStateService $simulatedState,
        protected InterviewerSimulationScenarioService $scenarioService,
        protected InboundBookingWebhookService $inboundService
    ) {}

    /**
     * Main Integration Simulator Hub view.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = $hotelId ? Hotel::find($hotelId) : Hotel::where('is_active', true)->first();

        if ($hotel && session('current_hotel_id') !== $hotel->id) {
            session(['current_hotel_id' => $hotel->id]);
        }

        $hotelId = $hotel?->id ?? 1;

        // Ensure all 7 simulators are provisioned for this hotel
        foreach (IntegrationModeService::PARTNERS as $code => $name) {
            $this->simulatedState->ensureProvisioned($hotelId, $code);
        }

        $partnerModes = $this->modeService->getAllPartnerModes();
        $globalMode = $this->modeService->getMode();
        $globalBadge = $this->modeService->getModeBadge();

        $properties = SimulatedPartnerProperty::where('hotel_id', $hotelId)
            ->withCount(['roomMappings', 'inventories'])
            ->get()
            ->keyBy('partner_code');

        $activeFailures = SimulatedFailureConfig::where('is_active', true)->get()->keyBy('partner_code');

        $recentRequests = SimulatedPartnerRequest::latest()
            ->take(12)
            ->get();

        $recentInboundBookings = SimulatedPartnerReservation::latest()
            ->take(8)
            ->get();

        $scenarios = $this->scenarioService->getScenarioCatalog();
        $roomTypes = RoomType::where('hotel_id', $hotelId)->get();

        return view('simulators.index', compact(
            'hotel',
            'partnerModes',
            'globalMode',
            'globalBadge',
            'properties',
            'activeFailures',
            'recentRequests',
            'recentInboundBookings',
            'scenarios',
            'roomTypes'
        ));
    }

    /**
     * Switch integration mode (SIMULATOR, SANDBOX, LIVE).
     */
    public function switchMode(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:simulator,sandbox,live',
            'partner_code' => 'nullable|string',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $record = $this->modeService->setMode(
                $request->input('mode'),
                $request->input('partner_code'),
                Auth::id(),
                $request->input('notes')
            );

            return back()->with('success', "Integration mode successfully updated to [{$record->mode}] for " . ($record->partner_code ?? 'Global'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Configure or clear failure injection rules.
     */
    public function configureFailure(Request $request)
    {
        $request->validate([
            'partner_code' => 'required|string',
            'operation' => 'nullable|string',
            'failure_type' => 'required|string',
            'http_status' => 'nullable|integer',
            'latency_ms' => 'nullable|integer|min:0|max:5000',
            'error_message' => 'nullable|string|max:255',
            'action' => 'required|in:set,clear,clear_all',
        ]);

        $partnerCode = $request->input('partner_code');
        $operation = $request->input('operation', 'all');

        if ($request->input('action') === 'clear_all') {
            SimulatedFailureConfig::query()->update(['is_active' => false]);
            return back()->with('success', 'All simulator failure injection rules have been deactivated.');
        }

        if ($request->input('action') === 'clear') {
            SimulatedFailureConfig::where('partner_code', $partnerCode)->update(['is_active' => false]);
            return back()->with('success', "Failure injection cleared for [{$partnerCode}].");
        }

        $type = $request->input('failure_type');
        $status = match ($type) {
            '504_timeout' => 504,
            '429_rate_limit' => 429,
            '500_server_error' => 500,
            '400_bad_request' => 400,
            '401_unauthorized' => 401,
            '403_forbidden' => 403,
            '409_conflict' => 409,
            default => (int) ($request->input('http_status') ?: 500),
        };

        SimulatedFailureConfig::updateOrCreate(
            [
                'partner_code' => $partnerCode,
                'operation' => $operation,
            ],
            [
                'failure_type' => $type,
                'http_status' => $status,
                'latency_ms' => (int) $request->input('latency_ms', 0),
                'error_code' => strtoupper($type),
                'error_message' => $request->input('error_message') ?? "Simulated fault: {$type}",
                'is_active' => true,
            ]
        );

        AuditLog::record('simulators.failure_injected', null, null, [
            'partner' => $partnerCode,
            'type' => $type,
            'http_status' => $status,
        ]);

        return back()->with('success', "Failure injection active: [{$partnerCode}] will return HTTP {$status} on {$operation}.");
    }

    /**
     * Trigger inbound OTA booking simulation.
     */
    public function triggerInboundBooking(Request $request): JsonResponse
    {
        $request->validate([
            'partner_code' => 'required|string',
            'external_reservation_id' => 'required|string',
            'property_code' => 'required|string',
            'remote_room_code' => 'required|string',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'guest_name' => 'required|string|max:100',
            'guest_email' => 'nullable|email',
            'guest_count' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'idempotency_key' => 'nullable|string',
        ]);

        $payload = $request->only([
            'external_reservation_id',
            'property_code',
            'remote_room_code',
            'check_in_date',
            'check_out_date',
            'guest_name',
            'guest_email',
            'guest_count',
            'price',
            'idempotency_key',
        ]);

        $partnerCode = $request->input('partner_code');
        $result = $this->inboundService->processInboundBooking($partnerCode, $payload);

        $status = $result['status_code'] ?? ($result['success'] ? 201 : 400);

        return response()->json($result, $status);
    }

    /**
     * Execute one of the 15 Interviewer Demonstration Scenarios.
     */
    public function runScenario(Request $request, int $id): JsonResponse
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = $hotelId ? Hotel::find($hotelId) : Hotel::where('is_active', true)->first();

        $result = $this->scenarioService->executeScenario($id, $hotel->id, Auth::id());

        return response()->json([
            'success' => true,
            'scenario_id' => $id,
            'data' => $result,
        ]);
    }

    /**
     * Simulate payment transaction (Charge, Refund, Decline, Timeout).
     */
    public function simulatePayment(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:charge,refund,partial_refund,decline,timeout',
            'amount' => 'required|numeric|min:100',
            'currency' => 'nullable|string|size:3',
            'reservation_reference' => 'nullable|string',
            'card_token' => 'nullable|string',
        ]);

        $action = $request->input('action');
        $amount = (float) $request->input('amount');
        $currency = $request->input('currency', 'JPY');
        $ref = $request->input('reservation_reference') ?? 'RES-SIM-' . rand(1000, 9999);
        $txId = 'TX-SIM-' . strtoupper(Str::random(12));

        $result = match ($action) {
            'charge' => [
                'success' => true,
                'status' => 'settled',
                'status_code' => 200,
                'transaction_id' => $txId,
                'amount' => $amount,
                'currency' => $currency,
                'gateway' => 'NTT DATA / Agoda Pay Virtual Terminal (SIMULATED)',
                'card_brand' => 'Visa Virtual Commercial',
                'last_four' => '4242',
                'message' => "Payment of {$currency} " . number_format($amount) . " charged successfully.",
                'authorization_code' => 'AUTH-' . rand(100000, 999999),
            ],
            'refund' => [
                'success' => true,
                'status' => 'refunded',
                'status_code' => 200,
                'transaction_id' => $txId,
                'refunded_amount' => $amount,
                'currency' => $currency,
                'message' => "Full refund of {$currency} " . number_format($amount) . " processed.",
            ],
            'partial_refund' => [
                'success' => true,
                'status' => 'partially_refunded',
                'status_code' => 200,
                'transaction_id' => $txId,
                'refunded_amount' => round($amount * 0.5, 2),
                'currency' => $currency,
                'message' => "Partial refund of {$currency} " . number_format($amount * 0.5) . " processed.",
            ],
            'decline' => [
                'success' => false,
                'status' => 'declined',
                'status_code' => 402,
                'error_code' => 'CARD_DECLINED_INSUFFICIENT_FUNDS',
                'message' => 'Simulated virtual card declined: Insufficient funds or card expired.',
            ],
            'timeout' => [
                'success' => false,
                'status' => 'timeout',
                'status_code' => 504,
                'error_code' => 'PAYMENT_GATEWAY_TIMEOUT',
                'message' => 'Simulated payment gateway timeout: Upstream bank network unreachable after 5000ms.',
            ],
        };

        return response()->json([
            'environment' => 'SIMULATOR',
            'simulation_notice' => 'No real bank transfer or credit card charge occurred.',
            'result' => $result,
        ], $result['status_code'] ?? 200);
    }

    /**
     * Re-seed or reset simulated remote state for active hotel.
     */
    public function resetSimulatedData(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = $hotelId ? Hotel::find($hotelId) : Hotel::where('is_active', true)->first();

        // 1. Clear any active failure injections
        SimulatedFailureConfig::query()->update(['is_active' => false]);

        // 2. Initialize 14 days of remote inventory for each partner
        $totalSeeded = 0;
        foreach (IntegrationModeService::PARTNERS as $code => $name) {
            $totalSeeded += $this->simulatedState->initializeRemoteInventory($hotel->id, $code, 14);
        }

        return back()->with('success', "Simulated partner network reset: {$totalSeeded} remote day-inventory records aligned to central PMS. Injections cleared.");
    }
}
