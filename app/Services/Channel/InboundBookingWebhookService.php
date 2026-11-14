<?php

namespace App\Services\Channel;

use App\Models\AuditLog;
use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\EventTrace;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\SimulatedPartnerProperty;
use App\Models\SimulatedPartnerRequest;
use App\Models\SimulatedPartnerReservation;
use App\Models\SimulatedPartnerRoomMapping;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InboundBookingWebhookService
{
    public function __construct(
        protected SimulatedRemoteStateService $simulatedState,
        protected ChannelSynchronizationService $syncService
    ) {}

    /**
     * Process an inbound reservation from a simulated OTA partner.
     */
    public function processInboundBooking(string $partnerCode, array $payload): array
    {
        $startTime = microtime(true);
        $correlationId = (string) Str::uuid();

        // 1. Failure injection check for inbound webhooks
        $injectedFailure = $this->simulatedState->evaluateFailureInjection($partnerCode, 'booking_inbound', $payload);
        if ($injectedFailure) {
            $durationMs = round((microtime(true) - $startTime) * 1000) + ($injectedFailure['latency_ms'] ?? 15);
            $this->simulatedState->logSimulatedExchange(
                $partnerCode,
                'inbound_webhook',
                "/api/simulators/{$partnerCode}/inbound-reservation",
                $payload,
                $injectedFailure,
                $injectedFailure['status_code'],
                $durationMs,
                $correlationId
            );
            return $injectedFailure;
        }

        // 2. Validate essential fields
        $validationError = $this->validatePayload($payload);
        if ($validationError) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $this->simulatedState->logSimulatedExchange(
                $partnerCode,
                'inbound_webhook',
                "/api/simulators/{$partnerCode}/inbound-reservation",
                $payload,
                $validationError,
                $validationError['status_code'],
                $durationMs,
                $correlationId
            );
            return $validationError;
        }

        $extResId = (string) $payload['external_reservation_id'];
        $propCode = (string) $payload['property_code'];
        $remoteRoomCode = (string) ($payload['remote_room_code'] ?? $payload['room_type_code']);
        $idempotencyKey = $payload['idempotency_key'] ?? $payload['event_id'] ?? null;

        // 3. Duplicate Delivery Check (Idempotency)
        $existing = null;
        if ($idempotencyKey) {
            $existing = SimulatedPartnerReservation::where('partner_code', $partnerCode)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
        }
        if (!$existing) {
            $existing = SimulatedPartnerReservation::where('partner_code', $partnerCode)
                ->where('external_reservation_id', $extResId)
                ->first();
        }

        if ($existing) {
            $res = [
                'success' => true,
                'status' => 'duplicate_acknowledged',
                'message' => "Duplicate reservation detected with idempotency key [{$idempotencyKey}]. Acknowledged without double booking.",
                'partner_reference' => $extResId,
                'clearstay_reservation_id' => $existing->clearstay_reservation_id,
                'duplicate' => true,
                'status_code' => 200,
            ];
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $this->simulatedState->logSimulatedExchange(
                $partnerCode,
                'inbound_webhook',
                "/api/simulators/{$partnerCode}/inbound-reservation",
                $payload,
                $res,
                200,
                $durationMs,
                $correlationId
            );
            return $res;
        }

        // 4. Validate Property and Room Mapping
        $property = SimulatedPartnerProperty::where('partner_code', $partnerCode)
            ->where(function ($q) use ($propCode) {
                $q->where('property_code', $propCode)
                  ->orWhere('property_code', 'like', "%{$propCode}%");
            })
            ->first();

        if (!$property) {
            // Fallback to primary active hotel
            $hotel = Hotel::where('is_active', true)->first();
            if ($hotel) {
                $property = $this->simulatedState->ensureProvisioned($hotel->id, $partnerCode);
            }
        }

        if (!$property) {
            $err = [
                'success' => false,
                'status_code' => 404,
                'error_code' => 'PROPERTY_NOT_FOUND',
                'error' => "Property code '{$propCode}' is not registered with {$partnerCode} simulator.",
            ];
            $this->simulatedState->logSimulatedExchange($partnerCode, 'inbound_webhook', "/api/simulators/{$partnerCode}/inbound-reservation", $payload, $err, 404, 10, $correlationId);
            return $err;
        }

        $hotelId = $property->hotel_id;
        $mapping = SimulatedPartnerRoomMapping::where('property_id', $property->id)
            ->where('remote_room_code', $remoteRoomCode)
            ->first();

        if (!$mapping) {
            // Also check by ClearStay room type code directly
            $mapping = SimulatedPartnerRoomMapping::where('property_id', $property->id)
                ->whereHas('roomType', fn($q) => $q->where('code', $remoteRoomCode))
                ->first();
        }

        if (!$mapping) {
            $err = [
                'success' => false,
                'status_code' => 422,
                'error_code' => 'UNMAPPED_ROOM_CODE',
                'error' => "Remote room code '{$remoteRoomCode}' has no active mapping to ClearStay for property {$property->property_code}.",
            ];
            $this->simulatedState->logSimulatedExchange($partnerCode, 'inbound_webhook', "/api/simulators/{$partnerCode}/inbound-reservation", $payload, $err, 422, 10, $correlationId);
            return $err;
        }

        $roomTypeId = $mapping->room_type_id;
        $checkIn = Carbon::parse($payload['check_in_date'])->format('Y-m-d');
        $checkOut = Carbon::parse($payload['check_out_date'])->format('Y-m-d');

        if ($checkOut <= $checkIn) {
            $err = [
                'success' => false,
                'status_code' => 422,
                'error_code' => 'INVALID_DATE_RANGE',
                'error' => "Check-out date ({$checkOut}) must be strictly after check-in date ({$checkIn}).",
            ];
            $this->simulatedState->logSimulatedExchange($partnerCode, 'inbound_webhook', "/api/simulators/{$partnerCode}/inbound-reservation", $payload, $err, 422, 10, $correlationId);
            return $err;
        }

        // 5. Check Central Inventory Capacity (Locking Transaction)
        return DB::transaction(function () use (
            $partnerCode,
            $payload,
            $property,
            $mapping,
            $hotelId,
            $roomTypeId,
            $checkIn,
            $checkOut,
            $extResId,
            $idempotencyKey,
            $correlationId,
            $startTime
        ) {
            $stayDates = [];
            $curr = Carbon::parse($checkIn);
            $end = Carbon::parse($checkOut);

            while ($curr->lt($end)) {
                $stayDates[] = $curr->format('Y-m-d');
                $curr->addDay();
            }

            // Lock central inventory records for stay dates
            $inventories = CentralInventory::where('hotel_id', $hotelId)
                ->where('room_type_id', $roomTypeId)
                ->whereIn('date', $stayDates)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn($r) => $r->date->format('Y-m-d'));

            // Check if capacity exists on all nights
            foreach ($stayDates as $d) {
                $inv = $inventories->get($d);
                if (!$inv || $inv->available_units <= 0 || $inv->stop_sell) {
                    $err = [
                        'success' => false,
                        'status_code' => 409,
                        'error_code' => 'CAPACITY_EXHAUSTED',
                        'error' => "Inventory conflict on {$d}: Available units are 0 or stop-sell is active. Reservation rejected.",
                        'failed_stay_date' => $d,
                    ];
                    $durationMs = round((microtime(true) - $startTime) * 1000);
                    $this->simulatedState->logSimulatedExchange(
                        $partnerCode,
                        'inbound_webhook',
                        "/api/simulators/{$partnerCode}/inbound-reservation",
                        $payload,
                        $err,
                        409,
                        $durationMs,
                        $correlationId
                    );
                    return $err;
                }
            }

            // 6. Find or create fictional guest
            $guestName = $payload['guest_name'] ?? 'Simulated Guest';
            $guestParts = explode(' ', $guestName, 2);
            $guest = Guest::firstOrCreate(
                [
                    'hotel_id' => $hotelId,
                    'email' => $payload['guest_email'] ?? strtolower(Str::slug($guestName)) . '@example.test',
                ],
                [
                    'first_name' => $guestParts[0] ?? 'Simulated',
                    'last_name' => $guestParts[1] ?? 'Guest',
                    'phone' => $payload['guest_phone'] ?? '+81-90-0000-0000',
                ]
            );

            // 7. Find available physical room (or fallback to any room of this room type)
            $assignedRoom = Room::where('hotel_id', $hotelId)
                ->where('room_type_id', $roomTypeId)
                ->where('is_active', true)
                ->whereDoesntHave('reservations', function ($q) use ($checkIn, $checkOut) {
                    $q->whereIn('status', ['confirmed', 'checked_in'])
                      ->where('check_in_date', '<', $checkOut)
                      ->where('check_out_date', '>', $checkIn);
                })
                ->first();

            if (!$assignedRoom) {
                $assignedRoom = Room::where('hotel_id', $hotelId)
                    ->where('room_type_id', $roomTypeId)
                    ->first();
            }

            if (!$assignedRoom) {
                $assignedRoom = Room::create([
                    'hotel_id' => $hotelId,
                    'room_type_id' => $roomTypeId,
                    'room_number' => 'SIM-' . rand(100, 999),
                    'floor' => 1,
                    'status' => 'clean',
                    'is_active' => true,
                ]);
            }

            // 8. Find partner channel record
            $channel = Channel::where('code', $partnerCode)->first();

            // 9. Create Reservation in ClearStay PMS
            $totalAmount = (float) ($payload['price'] ?? $payload['total_amount'] ?? ($mapping->base_rate * count($stayDates)));
            $confirmationCode = 'CS-' . strtoupper($partnerCode[0]) . '-' . strtoupper(substr(md5($extResId . time()), 0, 8));

            $reservation = Reservation::create([
                'hotel_id' => $hotelId,
                'guest_id' => $guest->id,
                'room_id' => $assignedRoom?->id,
                'room_type_id' => $roomTypeId,
                'channel_id' => $channel?->id,
                'confirmation_code' => $confirmationCode,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'adults' => (int) ($payload['guest_count'] ?? 2),
                'children' => 0,
                'total_price' => $totalAmount,
                'status' => 'confirmed',
                'payment_status' => 'confirmed',
            ]);

            // 10. Decrement Central Inventory & Increment Reserved Units
            $updatedInventoryPayload = [];
            foreach ($stayDates as $d) {
                $inv = $inventories->get($d);
                if ($inv) {
                    $inv->reserved_units += 1;
                    $inv->available_units = max(0, $inv->available_units - 1);
                    $inv->save();

                    $updatedInventoryPayload[] = [
                        'room_type_id' => $roomTypeId,
                        'date' => $d,
                        'available' => $inv->available_units,
                        'rate' => $inv->base_rate,
                        'stop_sell' => $inv->stop_sell,
                    ];
                }
            }

            // 11. Record in Simulated Partner Reservations store
            $simRes = SimulatedPartnerReservation::create([
                'partner_code' => $partnerCode,
                'external_reservation_id' => $extResId,
                'property_code' => $property->property_code,
                'remote_room_code' => $mapping->remote_room_code,
                'guest_name' => $guestName,
                'guest_email' => $guest->email,
                'guest_phone' => $guest->phone,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'guest_count' => (int) ($payload['guest_count'] ?? 2),
                'total_amount' => $totalAmount,
                'currency' => $payload['currency'] ?? 'JPY',
                'status' => 'confirmed',
                'idempotency_key' => $idempotencyKey,
                'raw_payload' => $payload,
                'clearstay_reservation_id' => $reservation->id,
            ]);

            // 12. Also decrement this partner's own remote inventory
            foreach ($stayDates as $d) {
                $remoteInv = $property->inventories()
                    ->where('remote_room_code', $mapping->remote_room_code)
                    ->where('date', $d)
                    ->first();
                if ($remoteInv) {
                    $remoteInv->available_units = max(0, $remoteInv->available_units - 1);
                    $remoteInv->save();
                }
            }

            // 13. Broadcast new availability to OTHER connected channels via transactional outbox
            if (!empty($updatedInventoryPayload)) {
                $this->syncService->broadcastInventoryChange($hotelId, $updatedInventoryPayload, 'inbound_booking_sync');
            }

            AuditLog::record('channels.inbound_booking_processed', $reservation, null, [
                'partner' => $partnerCode,
                'external_reservation_id' => $extResId,
                'confirmation_code' => $confirmationCode,
                'hotel_id' => $hotelId,
            ]);

            $durationMs = round((microtime(true) - $startTime) * 1000);

            $response = [
                'success' => true,
                'status' => 'accepted',
                'status_code' => 201,
                'partner_reference' => $extResId,
                'confirmation_code' => $confirmationCode,
                'clearstay_reservation_id' => $reservation->id,
                'room_number' => $assignedRoom?->room_number ?? 'unassigned',
                'hotel_name' => $property->hotel->name,
                'nights' => count($stayDates),
                'total_amount' => $totalAmount,
                'currency' => $payload['currency'] ?? 'JPY',
                'processed_at' => now()->toIso8601String(),
                'environment' => 'SIMULATOR',
            ];

            $this->simulatedState->logSimulatedExchange(
                $partnerCode,
                'inbound_webhook',
                "/api/simulators/{$partnerCode}/inbound-reservation",
                $payload,
                $response,
                201,
                $durationMs,
                $correlationId
            );

            return $response;
        });
    }

    /**
     * Validate incoming webhook payload format.
     */
    protected function validatePayload(array $payload): ?array
    {
        $required = ['external_reservation_id', 'property_code', 'check_in_date', 'check_out_date'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                return [
                    'success' => false,
                    'status_code' => 400,
                    'error_code' => 'MISSING_REQUIRED_FIELD',
                    'error' => "Missing required field: {$field}",
                ];
            }
        }

        if (empty($payload['remote_room_code']) && empty($payload['room_type_code'])) {
            return [
                'success' => false,
                'status_code' => 400,
                'error_code' => 'MISSING_ROOM_CODE',
                'error' => 'Missing room type code identifier (remote_room_code or room_type_code)',
            ];
        }

        // Validate date formats
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $payload['check_in_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $payload['check_out_date'])) {
            return [
                'success' => false,
                'status_code' => 400,
                'error_code' => 'INVALID_DATE_FORMAT',
                'error' => 'Dates must be formatted in strict ISO YYYY-MM-DD format.',
            ];
        }

        return null;
    }
}
