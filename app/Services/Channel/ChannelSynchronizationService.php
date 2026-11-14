<?php

namespace App\Services\Channel;

use App\Models\AuditLog;
use App\Models\CentralInventory;
use App\Models\ChannelConnection;
use App\Models\Hotel;
use App\Models\SyncAttemptLog;
use App\Models\SyncOutboxEvent;
use App\Services\Channel\Adapters\AgodaAdapter;
use App\Services\Channel\Adapters\BookingComAdapter;
use App\Services\Channel\Adapters\DirectWebAdapter;
use App\Services\Channel\Adapters\ExpediaAdapter;
use App\Services\Channel\Adapters\JalanAdapter;
use App\Services\Channel\Adapters\MockSimulatorAdapter;
use App\Services\Channel\Adapters\RakutenAdapter;
use App\Services\Channel\Adapters\SakuraPmsAdapter;
use App\Services\Channel\Contracts\ChannelAdapterInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChannelSynchronizationService
{
    protected array $adapterMap = [
        'booking_com' => BookingComAdapter::class,
        'agoda' => AgodaAdapter::class,
        'expedia' => ExpediaAdapter::class,
        'rakuten' => RakutenAdapter::class,
        'jalan' => JalanAdapter::class,
        'direct_web' => DirectWebAdapter::class,
        'sakura_pms' => SakuraPmsAdapter::class,
        'ota_simulator' => MockSimulatorAdapter::class,
        'demo_ota' => MockSimulatorAdapter::class,
    ];

    /**
     * Resolve the adapter instance for a channel code.
     */
    public function getAdapter(string $channelCode): ChannelAdapterInterface
    {
        $class = $this->adapterMap[$channelCode] ?? MockSimulatorAdapter::class;
        return app($class);
    }

    /**
     * Update Once, Synchronize Everywhere:
     * Commits central inventory change and queues transactional outbox events to all active connected channels.
     * Optionally excludes the originating channel to avoid double-processing inbound sales.
     */
    public function broadcastInventoryChange(int $hotelId, array $inventoryItems, string $eventType = 'inventory_sync', ?string $excludeChannelCode = null): array
    {
        $correlationId = (string) Str::uuid();
        $query = ChannelConnection::where('hotel_id', $hotelId)
            ->where('is_enabled', true)
            ->with('channel');

        if ($excludeChannelCode) {
            $query->whereHas('channel', function ($q) use ($excludeChannelCode) {
                $q->where('code', '!=', $excludeChannelCode);
            });
        }

        $connections = $query->get();

        $dispatchedEvents = [];

        DB::transaction(function () use ($hotelId, $inventoryItems, $eventType, $correlationId, $connections, &$dispatchedEvents) {
            foreach ($connections as $connection) {
                $outboxEvent = SyncOutboxEvent::create([
                    'hotel_id' => $hotelId,
                    'channel_connection_id' => $connection->id,
                    'correlation_id' => $correlationId,
                    'event_type' => $eventType,
                    'payload' => $inventoryItems,
                    'status' => 'pending',
                    'attempts' => 0,
                    'scheduled_at' => now(),
                ]);

                // Immediately process or dispatch
                $result = $this->executeSyncEvent($outboxEvent);
                $dispatchedEvents[] = [
                    'event_id' => $outboxEvent->id,
                    'channel' => $connection->channel->name,
                    'channel_code' => $connection->channel->code,
                    'status' => $result['status'],
                    'message' => $result['message'] ?? null,
                ];
            }
        });

        AuditLog::record('channels.inventory_broadcast', null, null, [
            'hotel_id' => $hotelId,
            'correlation_id' => $correlationId,
            'channels_count' => count($connections),
            'items_count' => count($inventoryItems),
        ]);

        return [
            'correlation_id' => $correlationId,
            'channels_notified' => count($connections),
            'results' => $dispatchedEvents,
        ];
    }

    /**
     * Execute an individual sync event against the adapter.
     */
    public function executeSyncEvent(SyncOutboxEvent $event): array
    {
        $connection = $event->channelConnection;
        if (!$connection) {
            $event->update(['status' => 'failed', 'last_error' => 'No channel connection found']);
            return ['status' => 'failed', 'message' => 'Missing connection'];
        }

        $adapter = $this->getAdapter($connection->channel->code);
        $event->increment('attempts');
        $event->update(['status' => 'processing']);

        $startTime = microtime(true);
        $response = $adapter->pushInventory($connection, $event->payload);
        $durationMs = round((microtime(true) - $startTime) * 1000);

        // Record attempt log
        SyncAttemptLog::create([
            'sync_outbox_event_id' => $event->id,
            'attempt_number' => $event->attempts,
            'http_status' => $response['status_code'] ?? 200,
            'request_payload' => $event->payload,
            'response_payload' => $response,
            'execution_time_ms' => $durationMs,
            'error_message' => $response['success'] ? null : ($response['error'] ?? 'Sync failed'),
        ]);

        if (!empty($response['success'])) {
            $event->update([
                'status' => 'confirmed',
                'executed_at' => now(),
                'last_error' => null,
            ]);
            $connection->update([
                'last_successful_sync' => now(),
                'sync_status' => 'ok',
            ]);
            return ['status' => 'confirmed', 'message' => $response['message'] ?? 'Synchronized'];
        } else {
            $isDeadLetter = $event->attempts >= 5;
            $newStatus = $isDeadLetter ? 'dead_letter' : 'failed';

            $event->update([
                'status' => $newStatus,
                'last_error' => $response['error'] ?? 'Synchronization rejected by channel',
            ]);
            $connection->increment('error_count');
            $connection->update(['sync_status' => 'failed']);
            return ['status' => $newStatus, 'message' => $response['error'] ?? 'Failed'];
        }
    }

    /**
     * Retry a failed sync outbox event.
     */
    public function retrySyncEvent(int $eventId): array
    {
        $event = SyncOutboxEvent::findOrFail($eventId);
        return $this->executeSyncEvent($event);
    }

    /**
     * Retry all failed or pending sync events for a hotel.
     */
    public function retryAllFailed(int $hotelId): int
    {
        $failedEvents = SyncOutboxEvent::where('hotel_id', $hotelId)
            ->whereIn('status', ['failed', 'dead_letter'])
            ->get();

        $recovered = 0;
        foreach ($failedEvents as $event) {
            $result = $this->executeSyncEvent($event);
            if ($result['status'] === 'confirmed') {
                $recovered++;
            }
        }

        return $recovered;
    }

    /**
     * Execute reconciliation check between central inventory and channel.
     */
    public function reconcileChannel(ChannelConnection $connection, string $dateFrom, string $dateTo): array
    {
        $adapter = $this->getAdapter($connection->channel->code);
        $result = $adapter->reconcile($connection, $dateFrom, $dateTo);

        $connection->update([
            'sync_status' => 'ok',
            'last_successful_sync' => now(),
        ]);

        AuditLog::record('channels.reconciliation_completed', null, null, [
            'hotel_id' => $connection->hotel_id,
            'channel_id' => $connection->channel_id,
            'status' => $result['status'],
        ]);

        return $result;
    }
}
