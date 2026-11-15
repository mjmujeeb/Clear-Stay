<?php

namespace App\Services\Inventory;

use App\Models\AuditLog;
use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\SimulatedPartnerInventory;
use App\Models\SimulatedPartnerProperty;
use App\Models\SimulatedPartnerRoomMapping;
use App\Models\SyncOutboxEvent;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RoomAvailabilityMatrixService
{
    /**
     * Standard simulated partner definitions.
     */
    public const SIMULATED_PARTNERS = [
        'booking_com' => [
            'name' => 'Booking.com',
            'label' => 'Booking.com Simulator',
            'icon' => 'fa-solid fa-b',
            'color' => '#003580',
        ],
        'agoda' => [
            'name' => 'Agoda',
            'label' => 'Agoda Simulator',
            'icon' => 'fa-solid fa-a',
            'color' => '#002B49',
        ],
        'expedia' => [
            'name' => 'Expedia',
            'label' => 'Expedia Simulator',
            'icon' => 'fa-solid fa-plane-departure',
            'color' => '#FFDA00',
        ],
        'rakuten' => [
            'name' => 'Rakuten Travel',
            'label' => 'Rakuten Travel Simulator',
            'icon' => 'fa-solid fa-r',
            'color' => '#BF0000',
        ],
        'jalan' => [
            'name' => 'Jalan.net',
            'label' => 'Jalan.net Simulator',
            'icon' => 'fa-solid fa-j',
            'color' => '#FF5A00',
        ],
        'direct_web' => [
            'name' => 'Direct Web',
            'label' => 'Direct Booking Engine',
            'icon' => 'fa-solid fa-hotel',
            'color' => '#287F79',
        ],
    ];

    /**
     * Compute single-date live room availability matrix across PMS and all simulated OTAs.
     * Strictly read-only; does not mutate or silently insert database inventory.
     */
    public function getMatrixForDate(
        int $hotelId,
        string $date,
        ?int $roomTypeId = null,
        ?string $channelCode = null,
        ?string $statusFilter = null
    ): array {
        $dateStr = Carbon::parse($date)->format('Y-m-d');

        $roomTypeQuery = RoomType::where('hotel_id', $hotelId)->with('rooms');
        if ($roomTypeId) {
            $roomTypeQuery->where('id', $roomTypeId);
        }
        $roomTypes = $roomTypeQuery->orderBy('id')->get();

        $channels = Channel::where('is_active', true)->get();
        if ($channelCode) {
            $channels = $channels->where('code', $channelCode);
        }

        $matrixRows = [];
        $kpi = [
            'total_room_types' => $roomTypes->count(),
            'total_pms_capacity' => 0,
            'total_pms_reserved' => 0,
            'total_pms_maintenance' => 0,
            'total_pms_available' => 0,
            'matched_cells' => 0,
            'discrepancy_cells' => 0,
            'oversell_risk_cells' => 0,
            'low_stock_types' => 0,
            'sold_out_types' => 0,
        ];

        foreach ($roomTypes as $rt) {
            $physicalCapacity = $rt->total_rooms ?? $rt->rooms->count();
            if ($physicalCapacity === 0) {
                $physicalCapacity = $rt->rooms->count() ?: 10;
            }

            // Central PMS inventory lookup
            $central = CentralInventory::where('hotel_id', $hotelId)
                ->where('room_type_id', $rt->id)
                ->whereDate('date', $dateStr)
                ->first();

            $pmsData = [
                'has_record' => (bool) $central,
                'physical_capacity' => $physicalCapacity,
                'total_units' => $central ? $central->total_units : $physicalCapacity,
                'reserved_units' => $central ? $central->reserved_units : 0,
                'maintenance_units' => $central ? $central->maintenance_units : 0,
                'blocked_units' => $central ? (($central->stop_sell ? $central->available_units : 0) + $central->maintenance_units) : 0,
                'available_units' => $central ? $central->calculateAvailableUnits() : 0,
                'stop_sell' => $central ? (bool) $central->stop_sell : false,
                'base_rate' => $central ? $central->base_rate : $rt->base_price,
                'currency' => $central ? $central->currency : 'JPY',
                'status' => 'AVAILABLE',
            ];

            if (!$central) {
                $pmsData['status'] = 'MISSING_INVENTORY';
            } elseif ($pmsData['available_units'] === 0) {
                $pmsData['status'] = 'SOLD_OUT';
                $kpi['sold_out_types']++;
            } elseif ($pmsData['available_units'] <= 2) {
                $pmsData['status'] = 'LOW_STOCK';
                $kpi['low_stock_types']++;
            }

            $kpi['total_pms_capacity'] += $pmsData['total_units'];
            $kpi['total_pms_reserved'] += $pmsData['reserved_units'];
            $kpi['total_pms_maintenance'] += $pmsData['maintenance_units'];
            $kpi['total_pms_available'] += $pmsData['available_units'];

            // Channels availability lookup
            $channelsData = [];
            $hasRowDiscrepancy = false;
            $hasRowOversellRisk = false;

            foreach ($channels as $ch) {
                $partnerCode = $ch->code;
                $prop = SimulatedPartnerProperty::where('hotel_id', $hotelId)
                    ->where('partner_code', $partnerCode)
                    ->first();

                if (!$prop) {
                    $channelsData[$partnerCode] = [
                        'channel_id' => $ch->id,
                        'partner_code' => $partnerCode,
                        'channel_name' => $ch->name,
                        'is_configured' => false,
                        'is_mapped' => false,
                        'remote_units' => null,
                        'status' => 'NOT_CONFIGURED',
                        'status_label' => 'Not Configured',
                        'difference' => null,
                        'tooltip' => "Simulator property not configured for {$ch->name}",
                        'last_synced_at' => null,
                        'pending_events' => 0,
                        'failed_events' => 0,
                    ];
                    continue;
                }

                $mapping = SimulatedPartnerRoomMapping::where('property_id', $prop->id)
                    ->where('room_type_id', $rt->id)
                    ->first();

                if (!$mapping) {
                    $channelsData[$partnerCode] = [
                        'channel_id' => $ch->id,
                        'partner_code' => $partnerCode,
                        'channel_name' => $ch->name,
                        'is_configured' => true,
                        'is_mapped' => false,
                        'remote_units' => null,
                        'status' => 'NOT_MAPPED',
                        'status_label' => 'Not Mapped',
                        'difference' => null,
                        'tooltip' => "No room mapping found for {$rt->name} on {$ch->name}",
                        'last_synced_at' => null,
                        'pending_events' => 0,
                        'failed_events' => 0,
                    ];
                    continue;
                }

                // Query partner independent remote inventory
                $remoteInv = SimulatedPartnerInventory::where('property_id', $prop->id)
                    ->where('remote_room_code', $mapping->remote_room_code)
                    ->whereDate('date', $dateStr)
                    ->first();

                // Check outbox events for this channel
                $pendingEvents = SyncOutboxEvent::where('hotel_id', $hotelId)
                    ->where(function ($q) use ($ch, $partnerCode) {
                        $q->where('channel_connection_id', $ch->id)
                          ->orWhere('payload->partner_code', $partnerCode);
                    })
                    ->where('status', 'pending')
                    ->count();

                $failedEvents = SyncOutboxEvent::where('hotel_id', $hotelId)
                    ->where(function ($q) use ($ch, $partnerCode) {
                        $q->where('channel_connection_id', $ch->id)
                          ->orWhere('payload->partner_code', $partnerCode);
                    })
                    ->where('status', 'failed')
                    ->count();

                if (!$remoteInv) {
                    $channelsData[$partnerCode] = [
                        'channel_id' => $ch->id,
                        'partner_code' => $partnerCode,
                        'channel_name' => $ch->name,
                        'remote_room_code' => $mapping->remote_room_code,
                        'is_configured' => true,
                        'is_mapped' => true,
                        'remote_units' => null,
                        'status' => 'MISSING_REMOTE_INVENTORY',
                        'status_label' => 'Missing Remote Record',
                        'difference' => $pmsData['available_units'],
                        'tooltip' => "Remote inventory record missing for {$dateStr}. Expected {$pmsData['available_units']} units.",
                        'last_synced_at' => null,
                        'pending_events' => $pendingEvents,
                        'failed_events' => $failedEvents,
                    ];
                    $kpi['discrepancy_cells']++;
                    $hasRowDiscrepancy = true;
                    continue;
                }

                $remoteUnits = $remoteInv->available_units;
                $pmsUnits = $pmsData['available_units'];
                $diff = $pmsUnits - $remoteUnits;
                $lastSynced = $remoteInv->last_synced_at ? Carbon::parse($remoteInv->last_synced_at)->format('Y-m-d H:i') : null;

                // Status classification
                if (!$central) {
                    $cellStatus = 'PMS_MISSING';
                    $statusLabel = 'PMS Record Missing';
                } elseif ($remoteUnits > $pmsUnits) {
                    $cellStatus = 'OVERSELL_RISK';
                    $statusLabel = "Critical Oversell Risk (+".abs($diff).")";
                    $kpi['oversell_risk_cells']++;
                    $hasRowOversellRisk = true;
                } elseif ($remoteUnits !== $pmsUnits) {
                    $cellStatus = 'DISCREPANCY';
                    $statusLabel = "Discrepancy ({$diff})";
                    $kpi['discrepancy_cells']++;
                    $hasRowDiscrepancy = true;
                } else {
                    $cellStatus = 'MATCHED';
                    $statusLabel = 'Matched';
                    $kpi['matched_cells']++;
                }

                if ($failedEvents > 0) {
                    $cellStatus = 'OUT_OF_SYNC';
                    $statusLabel = 'Sync Failed (' . $failedEvents . ')';
                } elseif ($pendingEvents > 0 && $cellStatus === 'MATCHED') {
                    $cellStatus = 'PENDING';
                    $statusLabel = 'Sync Pending';
                }

                $tooltip = "{$ch->name} Simulator: {$remoteUnits} units on {$dateStr} (PMS: {$pmsUnits}). Remote Code: {$mapping->remote_room_code}. Last Sync: " . ($lastSynced ?: 'Pending');

                $channelsData[$partnerCode] = [
                    'channel_id' => $ch->id,
                    'partner_code' => $partnerCode,
                    'channel_name' => $ch->name,
                    'remote_room_code' => $mapping->remote_room_code,
                    'is_configured' => true,
                    'is_mapped' => true,
                    'remote_units' => $remoteUnits,
                    'status' => $cellStatus,
                    'status_label' => $statusLabel,
                    'difference' => $diff,
                    'tooltip' => $tooltip,
                    'last_synced_at' => $lastSynced,
                    'pending_events' => $pendingEvents,
                    'failed_events' => $failedEvents,
                ];
            }

            // Status filter filter check
            if ($statusFilter && $statusFilter !== 'all') {
                if ($statusFilter === 'matched' && ($hasRowDiscrepancy || $hasRowOversellRisk)) {
                    continue;
                }
                if ($statusFilter === 'discrepancy' && !$hasRowDiscrepancy) {
                    continue;
                }
                if ($statusFilter === 'oversell_risk' && !$hasRowOversellRisk) {
                    continue;
                }
                if ($statusFilter === 'low_stock' && $pmsData['status'] !== 'LOW_STOCK') {
                    continue;
                }
                if ($statusFilter === 'sold_out' && $pmsData['status'] !== 'SOLD_OUT') {
                    continue;
                }
            }

            $matrixRows[] = [
                'room_type_id' => $rt->id,
                'room_type_name' => $rt->name,
                'room_type_code' => $rt->code ?? ('RT-' . $rt->id),
                'max_occupancy' => $rt->capacity ?? $rt->max_occupancy ?? 2,
                'pms' => $pmsData,
                'channels' => $channelsData,
                'has_discrepancy' => $hasRowDiscrepancy,
                'has_oversell_risk' => $hasRowOversellRisk,
            ];
        }

        return [
            'view' => 'single',
            'date' => $dateStr,
            'hotel_id' => $hotelId,
            'channels' => $channels->values(),
            'rows' => $matrixRows,
            'kpi' => $kpi,
        ];
    }

    /**
     * Compute multi-night stay range live availability matrix.
     * Checkout date is strictly EXCLUDED from occupied nights.
     * Evaluates night-by-night availability and whole-stay feasibility.
     */
    public function getMatrixForStayRange(
        int $hotelId,
        string $checkInDate,
        string $checkOutDate,
        int $requestedRooms = 1,
        ?int $roomTypeId = null,
        ?string $channelCode = null
    ): array {
        $checkIn = Carbon::parse($checkInDate);
        $checkOut = Carbon::parse($checkOutDate);

        if ($checkOut->lte($checkIn)) {
            $checkOut = $checkIn->copy()->addDay();
        }

        // Night-by-night evaluation: check-in up to (check-out - 1 day)
        $nightsPeriod = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());
        $nights = [];
        foreach ($nightsPeriod as $night) {
            $nights[] = $night->format('Y-m-d');
        }

        $roomTypeQuery = RoomType::where('hotel_id', $hotelId)->with('rooms');
        if ($roomTypeId) {
            $roomTypeQuery->where('id', $roomTypeId);
        }
        $roomTypes = $roomTypeQuery->orderBy('id')->get();

        $channels = Channel::where('is_active', true)->get();
        if ($channelCode) {
            $channels = $channels->where('code', $channelCode);
        }

        $stayRows = [];

        foreach ($roomTypes as $rt) {
            $nightlyData = [];
            $minPmsAvailable = 999999;
            $maxPmsAvailable = 0;
            $insufficientDates = [];
            $hasStayDiscrepancy = false;
            $hasStayOversellRisk = false;

            foreach ($nights as $dateStr) {
                $singleNightMatrix = $this->getMatrixForDate($hotelId, $dateStr, $rt->id, $channelCode);
                $nightRow = $singleNightMatrix['rows'][0] ?? null;

                $pmsAvail = $nightRow ? $nightRow['pms']['available_units'] : 0;
                $minPmsAvailable = min($minPmsAvailable, $pmsAvail);
                $maxPmsAvailable = max($maxPmsAvailable, $pmsAvail);

                if ($pmsAvail < $requestedRooms) {
                    $insufficientDates[] = [
                        'date' => $dateStr,
                        'available' => $pmsAvail,
                        'requested' => $requestedRooms,
                        'deficit' => $requestedRooms - $pmsAvail,
                    ];
                }

                if ($nightRow && $nightRow['has_discrepancy']) {
                    $hasStayDiscrepancy = true;
                }
                if ($nightRow && $nightRow['has_oversell_risk']) {
                    $hasStayOversellRisk = true;
                }

                $nightlyData[$dateStr] = $nightRow;
            }

            if ($minPmsAvailable === 999999) {
                $minPmsAvailable = 0;
            }

            $canAccommodate = ($minPmsAvailable >= $requestedRooms);

            $stayRows[] = [
                'room_type_id' => $rt->id,
                'room_type_name' => $rt->name,
                'room_type_code' => $rt->code ?? ('RT-' . $rt->id),
                'max_occupancy' => $rt->capacity ?? $rt->max_occupancy ?? 2,
                'requested_rooms' => $requestedRooms,
                'min_pms_available' => $minPmsAvailable,
                'max_pms_available' => $maxPmsAvailable,
                'can_accommodate' => $canAccommodate,
                'insufficient_dates' => $insufficientDates,
                'has_discrepancy' => $hasStayDiscrepancy,
                'has_oversell_risk' => $hasStayOversellRisk,
                'nightly' => $nightlyData,
            ];
        }

        return [
            'view' => 'multi',
            'hotel_id' => $hotelId,
            'check_in_date' => $checkIn->format('Y-m-d'),
            'check_out_date' => $checkOut->format('Y-m-d'),
            'nights_count' => count($nights),
            'nights' => $nights,
            'channels' => $channels->values(),
            'rows' => $stayRows,
        ];
    }

    /**
     * Get detailed ledger inspection for an interactive quantity cell.
     */
    public function getQuantityDetails(int $hotelId, int $roomTypeId, string $date, ?string $channelCode = null): array
    {
        $dateStr = Carbon::parse($date)->format('Y-m-d');
        $hotel = Hotel::find($hotelId);
        $roomType = RoomType::where('hotel_id', $hotelId)->where('id', $roomTypeId)->firstOrFail();

        $central = CentralInventory::where('hotel_id', $hotelId)
            ->where('room_type_id', $roomTypeId)
            ->whereDate('date', $dateStr)
            ->first();

        $pmsAvailable = $central ? $central->calculateAvailableUnits() : 0;

        $remoteData = null;
        if ($channelCode && $channelCode !== 'pms') {
            $channel = Channel::where('code', $channelCode)->first();
            $prop = SimulatedPartnerProperty::where('hotel_id', $hotelId)
                ->where('partner_code', $channelCode)
                ->first();

            $mapping = $prop ? SimulatedPartnerRoomMapping::where('property_id', $prop->id)
                ->where('room_type_id', $roomTypeId)
                ->first() : null;

            $remoteInv = ($prop && $mapping) ? SimulatedPartnerInventory::where('property_id', $prop->id)
                ->where('remote_room_code', $mapping->remote_room_code)
                ->whereDate('date', $dateStr)
                ->first() : null;

            $outboxEvents = SyncOutboxEvent::where('hotel_id', $hotelId)
                ->where(function ($q) use ($channel, $channelCode) {
                    if ($channel) {
                        $q->where('channel_connection_id', $channel->id);
                    }
                    $q->orWhere('payload->partner_code', $channelCode);
                })
                ->latest()
                ->take(5)
                ->get();

            $remoteUnits = $remoteInv ? $remoteInv->available_units : null;
            $diff = ($remoteUnits !== null) ? ($pmsAvailable - $remoteUnits) : null;

            $reconciliationStatus = 'MATCHED';
            $recommendedAction = 'System and channel are in sync.';
            if ($remoteUnits === null) {
                $reconciliationStatus = 'MISSING_REMOTE_INVENTORY';
                $recommendedAction = "Remote inventory record does not exist on {$channelCode} simulator. Re-aligning will provision and synchronize it.";
            } elseif ($diff < 0) {
                $reconciliationStatus = 'OVERSELL_RISK';
                $recommendedAction = "CRITICAL RISK: {$channelCode} offers ".abs($diff)." more units than PMS has. Immediate re-alignment is strongly recommended.";
            } elseif ($diff > 0) {
                $reconciliationStatus = 'DISCREPANCY';
                $recommendedAction = "Discrepancy: Central PMS has {$pmsAvailable} rooms, but {$channelCode} reports {$remoteUnits}. Re-alignment recommended.";
            }

            $remoteData = [
                'channel_name' => $channel ? $channel->name : strtoupper($channelCode),
                'partner_code' => $channelCode,
                'is_mapped' => (bool) $mapping,
                'remote_room_code' => $mapping?->remote_room_code,
                'remote_available_units' => $remoteUnits,
                'difference' => $diff,
                'reconciliation_status' => $reconciliationStatus,
                'recommended_action' => $recommendedAction,
                'last_synced_at' => $remoteInv?->last_synced_at?->format('Y-m-d H:i:s'),
                'outbox_events' => $outboxEvents,
            ];
        }

        // Active reservations occupying this room type on this date
        $reservations = Reservation::where('hotel_id', $hotelId)
            ->where('room_type_id', $roomTypeId)
            ->where('check_in_date', '<=', $dateStr)
            ->where('check_out_date', '>', $dateStr)
            ->whereNotIn('status', ['cancelled'])
            ->with(['guest', 'room'])
            ->get()
            ->map(function ($res) {
                return [
                    'id' => $res->id,
                    'reservation_number' => $res->reservation_number ?? ('RES-' . $res->id),
                    'guest_name' => $res->guest?->name ?? 'Guest #' . $res->id,
                    'rooms_count' => $res->rooms_count ?? 1,
                    'status' => $res->status,
                    'room_number' => $res->room?->room_number ?? 'Auto-Assigned',
                    'check_in' => Carbon::parse($res->check_in_date)->format('Y-m-d'),
                    'check_out' => Carbon::parse($res->check_out_date)->format('Y-m-d'),
                ];
            });

        return [
            'hotel_name' => $hotel?->name ?? 'Selected Hotel',
            'room_type_name' => $roomType->name,
            'room_type_code' => $roomType->code ?? ('RT-' . $roomType->id),
            'stay_date' => $dateStr,
            'pms' => [
                'has_record' => (bool) $central,
                'total_units' => $central ? $central->total_units : ($roomType->rooms->count() ?: 10),
                'reserved_units' => $central ? $central->reserved_units : 0,
                'maintenance_units' => $central ? $central->maintenance_units : 0,
                'available_units' => $pmsAvailable,
                'stop_sell' => $central ? (bool) $central->stop_sell : false,
                'base_rate' => $central ? $central->base_rate : $roomType->base_price,
            ],
            'channel' => $remoteData,
            'reservations' => $reservations,
        ];
    }

    /**
     * Re-align channel simulator inventory to match Central PMS.
     */
    public function realignChannelInventory(int $hotelId, int $roomTypeId, string $date, string $partnerCode, ?int $userId = null): array
    {
        $dateStr = Carbon::parse($date)->format('Y-m-d');

        $central = CentralInventory::where('hotel_id', $hotelId)
            ->where('room_type_id', $roomTypeId)
            ->whereDate('date', $dateStr)
            ->first();

        $pmsAvailable = $central ? $central->calculateAvailableUnits() : 0;

        $prop = SimulatedPartnerProperty::firstOrCreate(
            ['hotel_id' => $hotelId, 'partner_code' => $partnerCode],
            [
                'property_code' => strtoupper($partnerCode) . "-H{$hotelId}",
                'name' => "Simulated {$partnerCode} Property",
                'currency' => 'JPY',
                'timezone' => 'Asia/Tokyo',
                'is_connected' => true,
            ]
        );

        $mapping = SimulatedPartnerRoomMapping::firstOrCreate(
            ['property_id' => $prop->id, 'room_type_id' => $roomTypeId],
            [
                'partner_code' => $partnerCode,
                'remote_room_code' => strtoupper($partnerCode) . "-RT{$roomTypeId}",
                'remote_room_name' => "Remote Room {$roomTypeId}",
                'remote_rate_plan_code' => 'BAR',
                'max_occupancy' => 2,
                'base_rate' => 12000,
                'is_active' => true,
            ]
        );

        $remoteInv = SimulatedPartnerInventory::updateOrCreate(
            [
                'property_id' => $prop->id,
                'remote_room_code' => $mapping->remote_room_code,
                'date' => $dateStr,
            ],
            [
                'partner_code' => $partnerCode,
                'available_units' => $pmsAvailable,
                'last_synced_at' => now(),
            ]
        );

        AuditLog::record('inventory.matrix_realign', null, null, [
            'hotel_id' => $hotelId,
            'room_type_id' => $roomTypeId,
            'partner_code' => $partnerCode,
            'date' => $dateStr,
            'realigned_units' => $pmsAvailable,
            'actor_user_id' => $userId,
        ]);

        return [
            'success' => true,
            'message' => "Successfully synchronized {$partnerCode} remote inventory to {$pmsAvailable} units.",
            'available_units' => $remoteInv->available_units,
            'last_synced_at' => $remoteInv->last_synced_at->format('Y-m-d H:i:s'),
        ];
    }
}
