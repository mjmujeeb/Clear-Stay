<?php

namespace App\Services\Channel;

use App\Models\CentralInventory;
use App\Models\ChannelConnection;
use App\Models\EventTrace;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\SimulatedFailureConfig;
use App\Models\SimulatedPartnerInventory;
use App\Models\SimulatedPartnerProperty;
use App\Models\SimulatedPartnerRequest;
use App\Models\SimulatedPartnerReservation;
use App\Models\SimulatedPartnerRoomMapping;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimulatedRemoteStateService
{
    public const PARTNER_METADATA = [
        'booking_com' => [
            'name' => 'Booking.com — SIMULATED',
            'prefix' => 'BKG',
            'api_version' => 'B.XML_v2.4',
            'currency' => 'JPY',
            'room_prefix' => 'BKG_RM_',
            'rate_plan_default' => 'BKG_RATE_BAR',
        ],
        'agoda' => [
            'name' => 'Agoda — SIMULATED',
            'prefix' => 'AGD',
            'api_version' => 'YCS_v3.1_JSON',
            'currency' => 'JPY',
            'room_prefix' => 'AGD_RM_',
            'rate_plan_default' => 'AGD_RATE_YCS_FLEX',
        ],
        'expedia' => [
            'name' => 'Expedia — SIMULATED',
            'prefix' => 'EXP',
            'api_version' => 'PartnerCentral_v4.0',
            'currency' => 'JPY',
            'room_prefix' => 'EXP_RM_',
            'rate_plan_default' => 'EXP_RATE_STANDALONE',
        ],
        'rakuten' => [
            'name' => 'Rakuten Travel (楽天トラベル) — SIMULATED',
            'prefix' => 'RAK',
            'api_version' => 'NEOT_v2.0',
            'currency' => 'JPY',
            'room_prefix' => 'RAK_RM_',
            'rate_plan_default' => 'RAK_PLAN_BASIC',
        ],
        'jalan' => [
            'name' => 'Jalan.net (じゃらんnet) — SIMULATED',
            'prefix' => 'JAL',
            'api_version' => 'JalanWebAPI_v3.2',
            'currency' => 'JPY',
            'room_prefix' => 'JAL_RM_',
            'rate_plan_default' => 'JAL_PLAN_STANDARD',
        ],
        'direct_web' => [
            'name' => 'Direct Booking Engine — SIMULATED',
            'prefix' => 'DIR',
            'api_version' => 'ClearStayDirect_v1.0',
            'currency' => 'JPY',
            'room_prefix' => 'DIR_RM_',
            'rate_plan_default' => 'DIR_MEMBER_EXCLUSIVE',
        ],
        'sakura_pms' => [
            'name' => 'Sakura PMS Connector — SIMULATED',
            'prefix' => 'SAK',
            'api_version' => 'OpenExchange_v2.1',
            'currency' => 'JPY',
            'room_prefix' => 'SAK_RM_',
            'rate_plan_default' => 'SAK_FOLIO_STANDARD',
        ],
    ];

    /**
     * Ensure simulated property and room mappings are provisioned for a hotel and partner.
     */
    public function ensureProvisioned(int $hotelId, string $partnerCode): SimulatedPartnerProperty
    {
        $hotel = Hotel::findOrFail($hotelId);
        $meta = self::PARTNER_METADATA[$partnerCode] ?? self::PARTNER_METADATA['booking_com'];

        $property = SimulatedPartnerProperty::firstOrCreate(
            [
                'partner_code' => $partnerCode,
                'hotel_id' => $hotelId,
            ],
            [
                'property_code' => "{$meta['prefix']}-{$hotel->code}",
                'name' => "{$hotel->name} ({$meta['name']})",
                'currency' => $hotel->currency ?? 'JPY',
                'timezone' => 'Asia/Tokyo',
                'is_connected' => true,
                'api_version' => $meta['api_version'],
                'settings' => [
                    'supports_stop_sell' => true,
                    'supports_min_stay' => true,
                    'supports_cta_ctd' => true,
                    'supports_rate_delta' => true,
                ],
            ]
        );

        // Ensure room mappings exist for all hotel room types
        $roomTypes = RoomType::where('hotel_id', $hotelId)->get();
        foreach ($roomTypes as $rt) {
            $remoteCode = $meta['room_prefix'] . strtoupper(str_replace('-', '_', $rt->code));
            SimulatedPartnerRoomMapping::firstOrCreate(
                [
                    'property_id' => $property->id,
                    'remote_room_code' => $remoteCode,
                ],
                [
                    'partner_code' => $partnerCode,
                    'room_type_id' => $rt->id,
                    'remote_room_name' => "{$rt->name} [{$meta['prefix']}]",
                    'remote_rate_plan_code' => $meta['rate_plan_default'],
                    'max_occupancy' => $rt->max_occupancy ?? 2,
                    'base_rate' => $rt->base_price ?? 20000,
                    'is_active' => true,
                ]
            );
        }

        return $property;
    }

    /**
     * Seed initial remote inventory matching central inventory for the next N days.
     */
    public function initializeRemoteInventory(int $hotelId, string $partnerCode, int $days = 14): int
    {
        $property = $this->ensureProvisioned($hotelId, $partnerCode);
        $mappings = $property->roomMappings()->with('roomType')->get();
        $startDate = Carbon::today()->format('Y-m-d');
        $endDate = Carbon::today()->addDays($days - 1)->format('Y-m-d');

        $centralRecords = CentralInventory::where('hotel_id', $hotelId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get()
            ->keyBy(fn($r) => $r->room_type_id . '_' . $r->date->format('Y-m-d'));

        $created = 0;

        foreach ($mappings as $mapping) {
            for ($i = 0; $i < $days; $i++) {
                $dateStr = Carbon::today()->addDays($i)->format('Y-m-d');
                $central = $centralRecords->get($mapping->room_type_id . '_' . $dateStr);

                $units = $central ? $central->available_units : 5;
                $rate = $central ? $central->base_rate : $mapping->base_rate;
                $stopSell = $central ? (bool) $central->stop_sell : false;

                SimulatedPartnerInventory::updateOrCreate(
                    [
                        'partner_code' => $partnerCode,
                        'property_id' => $property->id,
                        'remote_room_code' => $mapping->remote_room_code,
                        'date' => $dateStr,
                    ],
                    [
                        'available_units' => $units,
                        'base_rate' => $rate,
                        'stop_sell' => $stopSell,
                        'min_stay' => 1,
                        'version' => 1,
                        'last_synced_at' => now(),
                    ]
                );
                $created++;
            }
        }

        return $created;
    }

    /**
     * Update remote inventory after a successful outbound push.
     */
    public function commitPushedInventory(int $hotelId, string $partnerCode, array $items): array
    {
        $property = $this->ensureProvisioned($hotelId, $partnerCode);
        $mappingsByRoomTypeId = $property->roomMappings->keyBy('room_type_id');
        $mappingsByRemoteCode = $property->roomMappings->keyBy('remote_room_code');

        $updatedCount = 0;

        foreach ($items as $item) {
            $mapping = null;
            if (!empty($item['room_type_id']) && isset($mappingsByRoomTypeId[$item['room_type_id']])) {
                $mapping = $mappingsByRoomTypeId[$item['room_type_id']];
            } elseif (!empty($item['remote_room_code']) && isset($mappingsByRemoteCode[$item['remote_room_code']])) {
                $mapping = $mappingsByRemoteCode[$item['remote_room_code']];
            }

            if (!$mapping) {
                continue;
            }

            $dateStr = is_string($item['date']) ? substr($item['date'], 0, 10) : $item['date']->format('Y-m-d');
            $units = isset($item['available']) ? (int) $item['available'] : (int) ($item['available_units'] ?? 0);
            $rate = $item['rate'] ?? $item['base_rate'] ?? null;
            $stopSell = isset($item['stop_sell']) ? (bool) $item['stop_sell'] : false;

            $record = SimulatedPartnerInventory::where([
                'partner_code' => $partnerCode,
                'property_id' => $property->id,
                'remote_room_code' => $mapping->remote_room_code,
                'date' => $dateStr,
            ])->first();

            if ($record) {
                $record->available_units = $units;
                if ($rate !== null) {
                    $record->base_rate = $rate;
                }
                $record->stop_sell = $stopSell;
                if (isset($item['min_stay'])) {
                    $record->min_stay = (int) $item['min_stay'];
                }
                $record->version += 1;
                $record->last_synced_at = now();
                $record->save();
            } else {
                SimulatedPartnerInventory::create([
                    'partner_code' => $partnerCode,
                    'property_id' => $property->id,
                    'remote_room_code' => $mapping->remote_room_code,
                    'date' => $dateStr,
                    'available_units' => $units,
                    'base_rate' => $rate ?? $mapping->base_rate,
                    'stop_sell' => $stopSell,
                    'min_stay' => $item['min_stay'] ?? 1,
                    'version' => 1,
                    'last_synced_at' => now(),
                ]);
            }

            $updatedCount++;
        }

        return [
            'success' => true,
            'updated_count' => $updatedCount,
            'property_code' => $property->property_code,
        ];
    }

    /**
     * Check if an active failure rule is injected for this partner & operation.
     */
    public function evaluateFailureInjection(string $partnerCode, string $operation, array $payload = []): ?array
    {
        $rule = SimulatedFailureConfig::getActiveFailure($partnerCode, $operation);

        if (!$rule || $rule->failure_type === 'none') {
            return null;
        }

        if ($rule->latency_ms > 0) {
            usleep(min($rule->latency_ms, 2500) * 1000);
        }

        $statusCode = $rule->http_status > 0 ? $rule->http_status : 500;
        $errorCode = $rule->error_code ?? 'SIMULATED_ERROR';
        $errorMessage = $rule->error_message ?? "Simulated fault injection for {$partnerCode}: {$rule->failure_type}";

        return [
            'success' => false,
            'channel' => $partnerCode,
            'status_code' => $statusCode,
            'error_code' => $errorCode,
            'error' => $errorMessage,
            'latency_ms' => $rule->latency_ms,
            'environment' => 'SIMULATOR',
            'simulated_failure' => true,
            'failure_type' => $rule->failure_type,
        ];
    }

    /**
     * Log request and response safely in database & EventTrace.
     */
    public function logSimulatedExchange(
        string $partnerCode,
        string $direction,
        string $endpoint,
        array $requestPayload,
        array $responsePayload,
        int $httpStatus,
        int $durationMs,
        ?string $correlationId = null
    ): SimulatedPartnerRequest {
        $cid = $correlationId ?? (string) Str::uuid();

        // Strip sensitive credentials/passwords if present
        $safeRequest = $this->sanitizePayload($requestPayload);
        $safeResponse = $this->sanitizePayload($responsePayload);

        $status = ($httpStatus >= 200 && $httpStatus < 300) ? 'success' : 'failure';

        // 1. Record simulated partner request log
        $reqLog = SimulatedPartnerRequest::create([
            'correlation_id' => $cid,
            'partner_code' => $partnerCode,
            'direction' => $direction,
            'endpoint' => $endpoint,
            'http_status' => $httpStatus,
            'duration_ms' => $durationMs,
            'request_payload' => $safeRequest,
            'response_payload' => $safeResponse,
            'status' => $status,
            'error_summary' => $status === 'failure' ? ($safeResponse['error'] ?? 'HTTP ' . $httpStatus) : null,
        ]);

        // 2. Also record unified EventTrace for the Observability Hub
        EventTrace::create([
            'trace_id' => $cid,
            'hotel_id' => session('current_hotel_id'),
            'operation' => "{$partnerCode}.{$direction}",
            'stage' => $status === 'success' ? 'completed' : 'failed',
            'status' => $status,
            'duration_ms' => $durationMs,
            'request_headers' => [
                'Content-Type' => 'application/json',
                'X-ClearStay-Mode' => 'SIMULATOR',
                'X-Partner-Code' => $partnerCode,
            ],
            'request_body' => $safeRequest,
            'response_headers' => ['HTTP/1.1' => (string) $httpStatus],
            'response_body' => $safeResponse,
            'error_details' => $status === 'failure' ? $safeResponse : null,
            'replayable' => true,
        ]);

        return $reqLog;
    }

    /**
     * Sanitize sensitive keys from logging.
     */
    protected function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = ['password', 'secret', 'api_key', 'card_number', 'cvv', 'token'];
        $clean = [];

        foreach ($payload as $k => $v) {
            if (is_array($v)) {
                $clean[$k] = $this->sanitizePayload($v);
            } elseif (in_array(strtolower($k), $sensitiveKeys, true)) {
                $clean[$k] = '***REDACTED***';
            } else {
                $clean[$k] = $v;
            }
        }

        return $clean;
    }
}
