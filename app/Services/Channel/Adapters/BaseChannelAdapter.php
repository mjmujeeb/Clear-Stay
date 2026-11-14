<?php

namespace App\Services\Channel\Adapters;

use App\Models\ChannelConnection;
use App\Services\Channel\Contracts\ChannelAdapterInterface;
use App\Services\Channel\SimulatedRemoteStateService;
use App\Services\Integration\IntegrationModeService;
use Illuminate\Support\Str;

abstract class BaseChannelAdapter implements ChannelAdapterInterface
{
    protected string $channelCode = 'generic';
    protected string $partnerName = 'Generic Channel';
    protected string $protocol = 'REST_JSON_v1';

    protected SimulatedRemoteStateService $simulatedState;
    protected IntegrationModeService $modeService;

    public function __construct(
        ?SimulatedRemoteStateService $simulatedState = null,
        ?IntegrationModeService $modeService = null
    ) {
        $this->simulatedState = $simulatedState ?? app(SimulatedRemoteStateService::class);
        $this->modeService = $modeService ?? app(IntegrationModeService::class);
    }

    public function getChannelCode(): string
    {
        return $this->channelCode;
    }

    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    public function getCapabilities(): array
    {
        return [
            'protocol' => $this->protocol,
            'inventory_push' => true,
            'rate_push' => true,
            'min_stay' => true,
            'max_stay' => true,
            'stop_sell' => true,
            'closed_to_arrival' => true,
            'closed_to_departure' => true,
            'reconciliation' => true,
            'inbound_reservations' => true,
        ];
    }

    public function testConnection(ChannelConnection $connection): array
    {
        $startTime = microtime(true);
        $correlationId = (string) Str::uuid();

        // 1. Check for intentional failure injection
        $injectedFailure = $this->simulatedState->evaluateFailureInjection($this->channelCode, 'connection_test', []);
        if ($injectedFailure) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $this->simulatedState->logSimulatedExchange(
                $this->channelCode,
                'outbound_push',
                "/api/simulators/{$this->channelCode}/handshake",
                ['connection_id' => $connection->id, 'action' => 'test_connection'],
                $injectedFailure,
                $injectedFailure['status_code'],
                $durationMs,
                $correlationId
            );
            return $injectedFailure;
        }

        $latency = rand(35, 80);
        $durationMs = round((microtime(true) - $startTime) * 1000) + $latency;

        $response = [
            'success' => true,
            'channel' => $this->channelCode,
            'partner_name' => $this->partnerName,
            'latency_ms' => $durationMs,
            'status_code' => 200,
            'message' => "Successfully connected to {$this->partnerName} endpoint.",
            'timestamp' => now()->toIso8601String(),
            'environment' => $this->modeService->getMode($this->channelCode),
            'protocol' => $this->protocol,
        ];

        $this->simulatedState->logSimulatedExchange(
            $this->channelCode,
            'outbound_push',
            "/api/simulators/{$this->channelCode}/handshake",
            ['connection_id' => $connection->id, 'action' => 'test_connection'],
            $response,
            200,
            $durationMs,
            $correlationId
        );

        return $response;
    }

    public function pushInventory(ChannelConnection $connection, array $inventoryData): array
    {
        $startTime = microtime(true);
        $correlationId = (string) Str::uuid();

        // 1. Evaluate failure injection lab
        $injectedFailure = $this->simulatedState->evaluateFailureInjection($this->channelCode, 'inventory_push', $inventoryData);
        if ($injectedFailure) {
            $durationMs = round((microtime(true) - $startTime) * 1000) + ($injectedFailure['latency_ms'] ?? 20);
            $this->simulatedState->logSimulatedExchange(
                $this->channelCode,
                'outbound_push',
                "/api/simulators/{$this->channelCode}/availability",
                $inventoryData,
                $injectedFailure,
                $injectedFailure['status_code'],
                $durationMs,
                $correlationId
            );
            return $injectedFailure;
        }

        // 2. Validate payload schema
        $validationError = $this->validateInventoryPayload($inventoryData);
        if ($validationError) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $this->simulatedState->logSimulatedExchange(
                $this->channelCode,
                'outbound_push',
                "/api/simulators/{$this->channelCode}/availability",
                $inventoryData,
                $validationError,
                400,
                $durationMs,
                $correlationId
            );
            return $validationError;
        }

        // 3. Commit state into independent remote database
        $commitResult = $this->simulatedState->commitPushedInventory($connection->hotel_id, $this->channelCode, $inventoryData);

        $latency = rand(30, 95);
        $durationMs = round((microtime(true) - $startTime) * 1000) + $latency;

        $response = [
            'success' => true,
            'channel' => $this->channelCode,
            'partner_name' => $this->partnerName,
            'correlation_id' => $correlationId,
            'status_code' => 200,
            'acknowledged_units' => count($inventoryData),
            'remote_records_updated' => $commitResult['updated_count'],
            'latency_ms' => $durationMs,
            'message' => "Inventory batch committed on {$this->partnerName}",
            'partner_reference' => strtoupper($this->channelCode) . '-ACK-' . rand(100000, 999999),
            'environment' => $this->modeService->getMode($this->channelCode),
            'processed_at' => now()->toIso8601String(),
        ];

        $this->simulatedState->logSimulatedExchange(
            $this->channelCode,
            'outbound_push',
            "/api/simulators/{$this->channelCode}/availability",
            $inventoryData,
            $response,
            200,
            $durationMs,
            $correlationId
        );

        return $response;
    }

    public function pushRates(ChannelConnection $connection, array $rateData): array
    {
        $startTime = microtime(true);
        $correlationId = (string) Str::uuid();

        // Check failure injection
        $injectedFailure = $this->simulatedState->evaluateFailureInjection($this->channelCode, 'rate_push', $rateData);
        if ($injectedFailure) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $this->simulatedState->logSimulatedExchange(
                $this->channelCode,
                'outbound_push',
                "/api/simulators/{$this->channelCode}/rates",
                $rateData,
                $injectedFailure,
                $injectedFailure['status_code'],
                $durationMs,
                $correlationId
            );
            return $injectedFailure;
        }

        $latency = rand(35, 110);
        $durationMs = round((microtime(true) - $startTime) * 1000) + $latency;

        $response = [
            'success' => true,
            'channel' => $this->channelCode,
            'partner_name' => $this->partnerName,
            'correlation_id' => $correlationId,
            'status_code' => 200,
            'acknowledged_plans' => count($rateData),
            'latency_ms' => $durationMs,
            'message' => "Rate updates synchronized with {$this->partnerName}",
            'partner_reference' => strtoupper($this->channelCode) . '-RATE-' . rand(100000, 999999),
            'environment' => $this->modeService->getMode($this->channelCode),
            'processed_at' => now()->toIso8601String(),
        ];

        $this->simulatedState->logSimulatedExchange(
            $this->channelCode,
            'outbound_push',
            "/api/simulators/{$this->channelCode}/rates",
            $rateData,
            $response,
            200,
            $durationMs,
            $correlationId
        );

        return $response;
    }

    public function reconcile(ChannelConnection $connection, string $dateFrom, string $dateTo): array
    {
        $startTime = microtime(true);

        $property = $this->simulatedState->ensureProvisioned($connection->hotel_id, $this->channelCode);
        $remoteItems = $property->inventories()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->get();

        $durationMs = round((microtime(true) - $startTime) * 1000) + rand(25, 60);

        return [
            'success' => true,
            'channel' => $this->channelCode,
            'partner_name' => $this->partnerName,
            'status' => 'synchronized',
            'records_evaluated' => $remoteItems->count(),
            'remote_inventory' => $remoteItems->map(fn($item) => [
                'remote_room_code' => $item->remote_room_code,
                'date' => $item->date->format('Y-m-d'),
                'available_units' => $item->available_units,
                'base_rate' => $item->base_rate,
                'stop_sell' => $item->stop_sell,
            ])->toArray(),
            'latency_ms' => $durationMs,
            'environment' => $this->modeService->getMode($this->channelCode),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate payload structure and return error if schema is violated.
     */
    protected function validateInventoryPayload(array $inventoryData): ?array
    {
        foreach ($inventoryData as $item) {
            // Check date format: must be valid ISO Y-m-d
            if (!empty($item['date']) && is_string($item['date'])) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', substr($item['date'], 0, 10))) {
                    return [
                        'success' => false,
                        'channel' => $this->channelCode,
                        'status_code' => 400,
                        'error_code' => 'INVALID_DATE_FORMAT',
                        'error' => "Date '{$item['date']}' does not match required YYYY-MM-DD schema for {$this->protocol}",
                        'simulated_failure' => true,
                    ];
                }
            }

            // Check availability bounds
            $avail = $item['available'] ?? $item['available_units'] ?? null;
            if ($avail !== null && (int)$avail < 0) {
                return [
                    'success' => false,
                    'channel' => $this->channelCode,
                    'status_code' => 400,
                    'error_code' => 'NEGATIVE_INVENTORY',
                    'error' => "Availability units cannot be negative ({$avail})",
                    'simulated_failure' => true,
                ];
            }
        }

        return null;
    }
}
