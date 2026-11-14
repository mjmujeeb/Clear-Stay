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
use App\Models\RoomType;
use App\Models\SimulatedPartnerProperty;
use App\Models\SimulatedPartnerRequest;
use App\Models\SimulatedPartnerReservation;
use App\Models\SimulatedPartnerRoomMapping;
use App\Models\SimulatedPartnerInventory;
use App\Models\SyncOutboxEvent;
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
     * Real-time Live Availability and Capacity Preview for Inbound Booking Simulator.
     */
    public function calculateAvailabilityPreview(
        int $hotelId,
        string $partnerCode,
        string $roomTypeCode,
        string $checkInDate,
        string $checkOutDate,
        int $requestedRooms = 1
    ): array {
        $requestedRooms = max(1, $requestedRooms);

        if (!$checkInDate || !$checkOutDate) {
            return [
                'success' => false,
                'error' => 'Check-in and check-out dates are required.',
            ];
        }

        try {
            $checkIn = Carbon::parse($checkInDate)->format('Y-m-d');
            $checkOut = Carbon::parse($checkOutDate)->format('Y-m-d');
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid date format. Expected YYYY-MM-DD.',
            ];
        }

        if ($checkOut <= $checkIn) {
            return [
                'success' => false,
                'error' => "Check-out date ({$checkOut}) must be strictly after check-in date ({$checkIn}).",
            ];
        }

        // Find room type
        $roomType = RoomType::where('hotel_id', $hotelId)
            ->where(function ($q) use ($roomTypeCode) {
                $q->where('code', $roomTypeCode)
                  ->orWhere('id', is_numeric($roomTypeCode) ? (int)$roomTypeCode : 0);
            })
            ->first();

        if (!$roomType) {
            // Try by remote room code mapping
            $mapping = SimulatedPartnerRoomMapping::where('remote_room_code', $roomTypeCode)->first();
            if ($mapping) {
                $roomType = $mapping->roomType;
            } else {
                $roomType = RoomType::where('hotel_id', $hotelId)->first();
            }
        }

        if (!$roomType) {
            return [
                'success' => false,
                'error' => 'Room type not found for the specified hotel.',
            ];
        }

        // Calculate stay dates (check-out date is excluded)
        $stayDates = [];
        $curr = Carbon::parse($checkIn);
        $end = Carbon::parse($checkOut);
        while ($curr->lt($end)) {
            $stayDates[] = $curr->format('Y-m-d');
            $curr->addDay();
        }

        // Physical room total capacity for fallback
        $physicalRoomsCount = Room::where('hotel_id', $hotelId)
            ->where('room_type_id', $roomType->id)
            ->where('is_active', true)
            ->count();
        if ($physicalRoomsCount === 0) {
            $physicalRoomsCount = $roomType->total_rooms ?? 5;
        }

        // Retrieve central inventory for stay dates
        $inventories = CentralInventory::where('hotel_id', $hotelId)
            ->where('room_type_id', $roomType->id)
            ->whereIn('date', $stayDates)
            ->get()
            ->keyBy(fn($r) => $r->date->format('Y-m-d'));

        $dateBreakdown = [];
        $minAvailable = null;
        $bottleneckDate = null;
        $maxTotalUnits = $physicalRoomsCount;
        $hasStopSell = false;

        foreach ($stayDates as $d) {
            $inv = $inventories->get($d);
            $totalUnits = $inv ? (int)$inv->total_units : $physicalRoomsCount;
            $reservedUnits = $inv ? (int)$inv->reserved_units : 0;
            $maintenanceUnits = $inv ? (int)$inv->maintenance_units : 0;
            $stopSell = $inv ? (bool)$inv->stop_sell : false;
            $rate = $inv ? (float)$inv->base_rate : (float)$roomType->base_price;

            $availBefore = $stopSell ? 0 : max(0, $totalUnits - $reservedUnits - $maintenanceUnits);
            $estRemaining = max(0, $availBefore - $requestedRooms);
            $canAcceptNight = (!$stopSell && $availBefore >= $requestedRooms);

            if ($stopSell) {
                $hasStopSell = true;
            }

            if ($minAvailable === null || $availBefore < $minAvailable) {
                $minAvailable = $availBefore;
                $bottleneckDate = $d;
            }

            if ($totalUnits > $maxTotalUnits) {
                $maxTotalUnits = $totalUnits;
            }

            $dateBreakdown[] = [
                'date' => $d,
                'day_name' => Carbon::parse($d)->format('D'),
                'total_units' => $totalUnits,
                'reserved_units' => $reservedUnits,
                'maintenance_units' => $maintenanceUnits,
                'available_before' => $availBefore,
                'requested_rooms' => $requestedRooms,
                'estimated_remaining' => $estRemaining,
                'stop_sell' => $stopSell,
                'rate' => $rate,
                'can_accept' => $canAcceptNight,
            ];
        }

        $minAvailableBefore = $minAvailable ?? 0;
        $canAccept = (!$hasStopSell && $minAvailableBefore >= $requestedRooms);
        $estimatedRemaining = max(0, $minAvailableBefore - $requestedRooms);

        // Status classification
        if (!$canAccept || $minAvailableBefore === 0) {
            $capacityStatus = 'exhausted';
            $statusLabel = 'Capacity Exhausted / Sold Out';
            $statusColor = 'red';
        } elseif ($estimatedRemaining <= 2) {
            $capacityStatus = 'low_inventory';
            $statusLabel = 'Limited Inventory Remaining';
            $statusColor = 'amber';
        } else {
            $capacityStatus = 'available';
            $statusLabel = 'Sufficient Inventory Available';
            $statusColor = 'green';
        }

        $capacityPercentage = $maxTotalUnits > 0 ? round(($minAvailableBefore / $maxTotalUnits) * 100) : 0;
        $remainingPercentage = $maxTotalUnits > 0 ? round(($estimatedRemaining / $maxTotalUnits) * 100) : 0;

        // Query live availability across all simulated partner channels
        $partners = [
            'booking_com' => 'Booking.com',
            'agoda' => 'Agoda',
            'expedia' => 'Expedia',
            'rakuten' => 'Rakuten Travel',
            'jalan' => 'Jalan.net',
        ];

        $partnerNetwork = [];
        // Add ClearStay Central
        $partnerNetwork[] = [
            'system' => 'ClearStay Central PMS',
            'code' => 'clearstay',
            'is_central' => true,
            'available_before' => $minAvailableBefore,
            'requested_rooms' => $requestedRooms,
            'estimated_remaining' => $estimatedRemaining,
            'status' => 'MATCHED',
            'sync_badge' => 'Primary Source of Truth',
        ];

        foreach ($partners as $code => $name) {
            $simProp = SimulatedPartnerProperty::where('hotel_id', $hotelId)
                ->where('partner_code', $code)
                ->first();

            $remoteAvail = null;
            if ($simProp) {
                $mapping = $simProp->roomMappings()->where('room_type_id', $roomType->id)->first();
                if ($mapping) {
                    $minRemote = SimulatedPartnerInventory::where('property_id', $simProp->id)
                        ->where('remote_room_code', $mapping->remote_room_code)
                        ->whereIn('date', $stayDates)
                        ->min('available_units');

                    if ($minRemote !== null) {
                        $remoteAvail = (int) $minRemote;
                    }
                }
            }

            if ($remoteAvail === null) {
                $remoteAvail = $minAvailableBefore;
            }

            $partnerEstRemaining = max(0, $remoteAvail - $requestedRooms);
            $isMatched = ($remoteAvail === $minAvailableBefore);

            $partnerNetwork[] = [
                'system' => $name . ' Simulator',
                'code' => $code,
                'is_central' => false,
                'is_originating' => ($code === $partnerCode),
                'available_before' => $remoteAvail,
                'requested_rooms' => $requestedRooms,
                'estimated_remaining' => $partnerEstRemaining,
                'status' => $isMatched ? 'MATCHED' : 'DISCREPANCY',
                'sync_badge' => $isMatched ? 'Synchronized' : 'Discrepancy Detected',
            ];
        }

        return [
            'success' => true,
            'hotel_id' => $hotelId,
            'partner_code' => $partnerCode,
            'room_type' => [
                'id' => $roomType->id,
                'name' => $roomType->name,
                'code' => $roomType->code,
                'base_price' => (float) $roomType->base_price,
            ],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => count($stayDates),
            'requested_rooms' => $requestedRooms,
            'available_before' => $minAvailableBefore,
            'estimated_remaining' => $estimatedRemaining,
            'can_accept' => $canAccept,
            'bottleneck_date' => $bottleneckDate,
            'has_stop_sell' => $hasStopSell,
            'capacity_status' => $capacityStatus,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
            'capacity_percentage' => $capacityPercentage,
            'remaining_percentage' => $remainingPercentage,
            'max_total_units' => $maxTotalUnits,
            'date_breakdown' => $dateBreakdown,
            'partner_network' => $partnerNetwork,
        ];
    }

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
        $roomsCount = max(1, (int) ($payload['rooms_count'] ?? $payload['requested_rooms'] ?? 1));

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
                'confirmation_code' => $existing->clearstayReservation?->confirmation_code,
                'clearstay_reservation_id' => $existing->clearstay_reservation_id,
                'rooms_count' => $existing->rooms_count ?? 1,
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
            $roomsCount,
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

            // Capture initial central inventory before booking
            $centralBefore = null;
            foreach ($stayDates as $d) {
                $inv = $inventories->get($d);
                $avail = $inv ? $inv->available_units : 0;
                if ($centralBefore === null || $avail < $centralBefore) {
                    $centralBefore = $avail;
                }
            }
            $centralBefore = $centralBefore ?? 0;

            // Capture partner pre-booking inventory levels
            $allPartners = [
                'booking_com' => 'Booking.com Simulator',
                'agoda' => 'Agoda Simulator',
                'expedia' => 'Expedia Simulator',
                'rakuten' => 'Rakuten Travel Simulator',
                'jalan' => 'Jalan.net Simulator',
            ];

            $partnersBefore = [];
            foreach ($allPartners as $pCode => $pName) {
                $pProp = SimulatedPartnerProperty::where('hotel_id', $hotelId)->where('partner_code', $pCode)->first();
                $minRem = null;
                if ($pProp) {
                    $pMap = $pProp->roomMappings()->where('room_type_id', $roomTypeId)->first();
                    if ($pMap) {
                        $minRem = SimulatedPartnerInventory::where('property_id', $pProp->id)
                            ->where('remote_room_code', $pMap->remote_room_code)
                            ->whereIn('date', $stayDates)
                            ->min('available_units');
                    }
                }
                $partnersBefore[$pCode] = $minRem !== null ? (int)$minRem : $centralBefore;
            }

            // Check if sufficient capacity exists on ALL nights of stay
            foreach ($stayDates as $d) {
                $inv = $inventories->get($d);
                if (!$inv || $inv->available_units < $roomsCount || $inv->stop_sell) {
                    $avail = $inv ? $inv->available_units : 0;
                    $err = [
                        'success' => false,
                        'status_code' => 409,
                        'error_code' => 'CAPACITY_EXHAUSTED',
                        'error' => "Inventory conflict on {$d}: Available units ({$avail}) is less than requested rooms ({$roomsCount}) or stop-sell is active. Reservation rejected.",
                        'failed_stay_date' => $d,
                        'available_units' => $avail,
                        'requested_rooms' => $roomsCount,
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

            // 7. Find physical rooms (for multi-room reservations)
            $freeRooms = Room::where('hotel_id', $hotelId)
                ->where('room_type_id', $roomTypeId)
                ->where('is_active', true)
                ->whereDoesntHave('reservations', function ($q) use ($checkIn, $checkOut) {
                    $q->whereIn('status', ['confirmed', 'checked_in'])
                      ->where('check_in_date', '<', $checkOut)
                      ->where('check_out_date', '>', $checkIn);
                })
                ->take($roomsCount)
                ->get();

            $assignedRoom = $freeRooms->first();
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
            $totalAmount = (float) ($payload['price'] ?? $payload['total_amount'] ?? ($mapping->base_rate * count($stayDates) * $roomsCount));
            $confirmationCode = 'CS-' . strtoupper($partnerCode[0]) . '-' . strtoupper(substr(md5($extResId . time()), 0, 8));
            $groupRef = $roomsCount > 1 ? ('GRP-' . strtoupper(Str::random(8))) : null;

            $reservation = Reservation::create([
                'hotel_id' => $hotelId,
                'guest_id' => $guest->id,
                'room_id' => $assignedRoom?->id,
                'room_type_id' => $roomTypeId,
                'channel_id' => $channel?->id,
                'confirmation_code' => $confirmationCode,
                'rooms_count' => $roomsCount,
                'group_booking_reference' => $groupRef,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'adults' => (int) ($payload['guest_count'] ?? (2 * $roomsCount)),
                'children' => 0,
                'total_price' => $totalAmount,
                'status' => 'confirmed',
                'payment_status' => 'confirmed',
            ]);

            // 10. Decrement Central Inventory by K rooms
            $updatedInventoryPayload = [];
            $centralAfter = null;

            foreach ($stayDates as $d) {
                $inv = $inventories->get($d);
                if ($inv) {
                    $inv->reserved_units += $roomsCount;
                    $inv->available_units = max(0, $inv->available_units - $roomsCount);
                    $inv->save();

                    if ($centralAfter === null || $inv->available_units < $centralAfter) {
                        $centralAfter = $inv->available_units;
                    }

                    $updatedInventoryPayload[] = [
                        'room_type_id' => $roomTypeId,
                        'date' => $d,
                        'available' => $inv->available_units,
                        'rate' => $inv->base_rate,
                        'stop_sell' => $inv->stop_sell,
                    ];
                }
            }
            $centralAfter = $centralAfter ?? max(0, $centralBefore - $roomsCount);

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
                'guest_count' => (int) ($payload['guest_count'] ?? (2 * $roomsCount)),
                'rooms_count' => $roomsCount,
                'total_amount' => $totalAmount,
                'currency' => $payload['currency'] ?? 'JPY',
                'status' => 'confirmed',
                'idempotency_key' => $idempotencyKey,
                'raw_payload' => $payload,
                'clearstay_reservation_id' => $reservation->id,
            ]);

            // 12. Decrement originating partner's own remote inventory by K rooms
            foreach ($stayDates as $d) {
                $remoteInv = $property->inventories()
                    ->where('remote_room_code', $mapping->remote_room_code)
                    ->where('date', $d)
                    ->first();
                if ($remoteInv) {
                    $remoteInv->available_units = max(0, $remoteInv->available_units - $roomsCount);
                    $remoteInv->save();
                }
            }

            // 13. Broadcast new availability to OTHER connected channels (excluding originating partner!)
            $syncResults = [];
            if (!empty($updatedInventoryPayload)) {
                $broadcast = $this->syncService->broadcastInventoryChange($hotelId, $updatedInventoryPayload, 'inbound_booking_sync', $partnerCode);
                $syncResults = $broadcast['results'] ?? [];
            }

            // 14. Build comprehensive Live Reconciliation table across all systems
            $partnerReconciliation = [];

            // Add Central PMS
            $partnerReconciliation[] = [
                'system' => 'ClearStay Central PMS',
                'partner_code' => 'clearstay',
                'is_central' => true,
                'is_originating' => false,
                'before' => $centralBefore,
                'delta' => -$roomsCount,
                'after' => $centralAfter,
                'status' => 'MATCHED',
                'message' => 'Authoritative Central Inventory updated',
            ];

            // Map sync dispatch results by channel code
            $syncByCode = [];
            foreach ($syncResults as $sr) {
                $code = $sr['channel_code'] ?? null;
                if ($code) {
                    $syncByCode[$code] = $sr;
                }
            }

            foreach ($allPartners as $pCode => $pName) {
                $beforeVal = $partnersBefore[$pCode] ?? $centralBefore;
                $isOriginating = ($pCode === $partnerCode);

                if ($isOriginating) {
                    // Originating partner was decremented directly
                    $afterVal = max(0, $beforeVal - $roomsCount);
                    $partnerReconciliation[] = [
                        'system' => $pName,
                        'partner_code' => $pCode,
                        'is_central' => false,
                        'is_originating' => true,
                        'before' => $beforeVal,
                        'delta' => -$roomsCount,
                        'after' => $afterVal,
                        'status' => 'MATCHED',
                        'message' => 'Originating Channel (sold externally first)',
                    ];
                } else {
                    $syncItem = $syncByCode[$pCode] ?? null;
                    $syncStatus = $syncItem['status'] ?? 'confirmed';
                    $outboxEventId = $syncItem['event_id'] ?? null;

                    // Query actual updated inventory on remote partner
                    $pProp = SimulatedPartnerProperty::where('hotel_id', $hotelId)->where('partner_code', $pCode)->first();
                    $actualAfter = null;
                    if ($pProp) {
                        $pMap = $pProp->roomMappings()->where('room_type_id', $roomTypeId)->first();
                        if ($pMap) {
                            $minRem = SimulatedPartnerInventory::where('property_id', $pProp->id)
                                ->where('remote_room_code', $pMap->remote_room_code)
                                ->whereIn('date', $stayDates)
                                ->min('available_units');
                            if ($minRem !== null) {
                                $actualAfter = (int) $minRem;
                            }
                        }
                    }

                    if ($actualAfter === null) {
                        $actualAfter = ($syncStatus === 'confirmed') ? max(0, $beforeVal - $roomsCount) : $beforeVal;
                    }

                    $expectedAfter = max(0, $beforeVal - $roomsCount);
                    $hasDiscrepancy = ($actualAfter !== $expectedAfter);

                    $finalStatus = 'MATCHED';
                    if ($syncStatus === 'failed' || $syncStatus === 'dead_letter') {
                        $finalStatus = 'FAILED';
                    } elseif ($hasDiscrepancy) {
                        $finalStatus = 'DISCREPANCY';
                    }

                    $partnerReconciliation[] = [
                        'system' => $pName,
                        'partner_code' => $pCode,
                        'is_central' => false,
                        'is_originating' => false,
                        'before' => $beforeVal,
                        'delta' => ($actualAfter !== $beforeVal) ? ($actualAfter - $beforeVal) : 0,
                        'after' => $actualAfter,
                        'status' => $finalStatus,
                        'event_id' => $outboxEventId,
                        'message' => $syncItem['message'] ?? ($finalStatus === 'MATCHED' ? 'Synchronized' : 'Sync error or discrepancy'),
                    ];
                }
            }

            AuditLog::record('channels.inbound_booking_processed', $reservation, null, [
                'partner' => $partnerCode,
                'external_reservation_id' => $extResId,
                'confirmation_code' => $confirmationCode,
                'rooms_count' => $roomsCount,
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
                'rooms_count' => $roomsCount,
                'group_booking_reference' => $groupRef,
                'room_number' => $assignedRoom?->room_number ?? 'unassigned',
                'assigned_rooms' => $freeRooms->pluck('room_number')->toArray(),
                'hotel_name' => $property->hotel->name,
                'stay_dates' => $stayDates,
                'nights' => count($stayDates),
                'total_amount' => $totalAmount,
                'currency' => $payload['currency'] ?? 'JPY',
                'inventory_summary' => [
                    'before' => $centralBefore,
                    'booked' => $roomsCount,
                    'remaining' => $centralAfter,
                ],
                'reconciliation' => $partnerReconciliation,
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
