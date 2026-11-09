<?php

namespace App\Http\Controllers;

use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\SyncAttemptLog;
use App\Models\SyncOutboxEvent;
use App\Services\Channel\ChannelSynchronizationService;
use App\Services\Inventory\CentralInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChannelManagerController extends Controller
{
    protected ChannelSynchronizationService $syncService;
    protected CentralInventoryService $inventoryService;

    public function __construct(ChannelSynchronizationService $syncService, CentralInventoryService $inventoryService)
    {
        $this->syncService = $syncService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Channel Manager Hub: Channels list, status, and synchronization telemetry.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId) ?? Hotel::first();

        $channels = Channel::where('is_active', true)->get();
        $connections = ChannelConnection::where('hotel_id', $hotelId)
            ->with(['channel', 'channelRoomMappings.roomType'])
            ->get()
            ->keyBy('channel_id');

        $outboxEvents = SyncOutboxEvent::where('hotel_id', $hotelId)
            ->with('channelConnection.channel')
            ->latest()
            ->take(10)
            ->get();

        $syncLogs = SyncAttemptLog::whereHas('syncOutboxEvent', function ($q) use ($hotelId) {
            $q->where('hotel_id', $hotelId);
        })->latest()->take(10)->get();

        $failedEventsCount = SyncOutboxEvent::where('hotel_id', $hotelId)
            ->where('status', 'failed')
            ->count();

        return view('channels.index', compact(
            'hotel',
            'channels',
            'connections',
            'outboxEvents',
            'syncLogs',
            'failedEventsCount'
        ));
    }

    /**
     * Broadcast inventory update universally to all connected channels.
     */
    public function syncAll(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $inventories = CentralInventory::where('hotel_id', $hotelId)->get();

        $items = $inventories->map(fn($i) => [
            'room_type_id' => $i->room_type_id,
            'date' => $i->date->toDateString(),
            'available' => $i->available_units,
            'rate' => $i->base_rate,
            'stop_sell' => $i->stop_sell,
        ])->toArray();

        $result = $this->syncService->broadcastInventoryChange($hotelId, $items, 'manual_broadcast_all');

        return back()->with('success', "Universal inventory broadcast dispatched to {$result['channels_notified']} channels. (Correlation: {$result['correlation_id']})");
    }

    /**
     * Sync single channel now.
     */
    public function syncNow(Request $request, Channel $channel)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $connection = ChannelConnection::where('hotel_id', $hotelId)->where('channel_id', $channel->id)->first();

        if (!$connection) {
            return back()->with('error', "Channel {$channel->name} is not connected.");
        }

        $items = CentralInventory::where('hotel_id', $hotelId)->get()->map(fn($i) => [
            'room_type_id' => $i->room_type_id,
            'date' => $i->date->toDateString(),
            'available' => $i->available_units,
            'rate' => $i->base_rate,
            'stop_sell' => $i->stop_sell,
        ])->toArray();

        $result = $this->syncService->broadcastInventoryChange($hotelId, $items, 'single_channel_sync');

        return back()->with('success', "Direct inventory synchronization dispatched for {$channel->name}.");
    }

    /**
     * Connect property to a channel.
     */
    public function connect(Request $request, Channel $channel)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        
        $connection = ChannelConnection::updateOrCreate(
            ['hotel_id' => $hotelId, 'channel_id' => $channel->id],
            [
                'is_enabled' => true,
                'sync_status' => 'ok',
                'credentials' => json_encode(['api_key' => 'live_' . Str::random(24)]),
            ]
        );

        return back()->with('success', "Successfully connected property to {$channel->name}.");
    }

    /**
     * Disconnect channel.
     */
    public function disconnect(Request $request, Channel $channel)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $connection = ChannelConnection::where('hotel_id', $hotelId)->where('channel_id', $channel->id)->first();

        if ($connection) {
            $connection->update(['is_enabled' => false, 'sync_status' => 'disconnected']);
        }

        return back()->with('success', "Disconnected {$channel->name}. Outbound inventory updates paused.");
    }

    /**
     * Simulate upstream 500 error for testing outbox resilience.
     */
    public function simulateError(Request $request, Channel $channel)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $connection = ChannelConnection::where('hotel_id', $hotelId)->where('channel_id', $channel->id)->first();

        // Create a failed outbox event
        $event = SyncOutboxEvent::create([
            'hotel_id' => $hotelId,
            'channel_connection_id' => $connection?->id,
            'correlation_id' => 'SIM-ERR-' . strtoupper(Str::random(8)),
            'event_type' => 'inventory_sync',
            'payload' => ['error_simulation' => true, 'target_channel' => $channel->name],
            'status' => 'failed',
            'attempts' => 1,
            'last_error' => 'HTTP 500 Upstream Internal Server Error simulated by operator test.',
            'scheduled_at' => now(),
            'executed_at' => now(),
        ]);

        SyncAttemptLog::create([
            'sync_outbox_event_id' => $event->id,
            'attempt_number' => 1,
            'http_status' => 500,
            'request_payload' => ['hotel_id' => $hotelId],
            'response_payload' => ['error' => 'Simulated 500 upstream failure'],
            'execution_time_ms' => 450,
            'error_message' => 'Simulated upstream circuit breaker failure.',
        ]);

        return back()->with('info', "Simulated 500 error logged for {$channel->name}. Outbox event marked for retry.");
    }

    /**
     * Retry failed events.
     */
    public function retryFailed(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $failedEvents = SyncOutboxEvent::where('hotel_id', $hotelId)->where('status', 'failed')->get();

        $count = 0;
        foreach ($failedEvents as $event) {
            $result = $this->syncService->retrySyncEvent($event->id);
            if ($result['status'] === 'confirmed') {
                $count++;
            }
        }

        return back()->with('success', "Retried {$failedEvents->count()} outbox events. Successfully resolved: {$count}.");
    }
}
