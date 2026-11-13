<?php

namespace App\Http\Controllers;

use App\Models\BulkChangeBatch;
use App\Models\CentralInventory;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Services\Inventory\CentralInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BulkEditorController extends Controller
{
    protected CentralInventoryService $inventoryService;

    public function __construct(CentralInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Bulk Editor Hub: forms, preview generator, and change history.
     * Strictly read-only on GET; never creates or mutates inventory records during page load.
     */
    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = $hotelId ? Hotel::find($hotelId) : Hotel::where('is_active', true)->first();
        if ($hotel && session('current_hotel_id') !== $hotel->id) {
            session(['current_hotel_id' => $hotel->id]);
        }

        $roomTypes = RoomType::where('hotel_id', $hotel?->id)->get();
        $batches = BulkChangeBatch::where('hotel_id', $hotel?->id)
            ->with(['user', 'rolledBackByUser'])
            ->latest()
            ->take(10)
            ->get();

        $recentInventories = CentralInventory::where('hotel_id', $hotel?->id)
            ->whereDate('date', '>=', Carbon::today()->format('Y-m-d'))
            ->with('roomType')
            ->orderBy('date')
            ->take(14)
            ->get();

        return view('bulk_editor.index', compact('hotel', 'roomTypes', 'batches', 'recentInventories'));
    }

    /**
     * AJAX endpoint to generate a Universal Update Preview before confirmation.
     */
    public function preview(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        if ($request->isMethod('get') && !$request->has('start_date')) {
            return redirect()->route('bulk_editor.index')->with('info', 'Please configure your bulk date range and criteria first.');
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $roomTypeIds = $request->input('room_type_ids');
        if (!$roomTypeIds && $request->filled('room_type_id')) {
            $roomTypeIds = [(int) $request->input('room_type_id')];
        }
        if (!$roomTypeIds) {
            $roomTypeIds = RoomType::where('hotel_id', $hotelId)->pluck('id')->toArray();
        }

        $criteria = [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'room_type_ids' => $roomTypeIds,
        ];

        $changes = array_filter([
            'available_units' => $request->available_units,
            'base_rate' => $request->base_rate,
            'price_delta' => $request->price_delta,
            'stop_sell' => $request->has('stop_sell') ? (bool) $request->stop_sell : null,
            'min_stay' => $request->min_stay,
        ], fn($v) => !is_null($v) && $v !== '');

        $preview = $this->inventoryService->generateBulkPreview($hotelId, $criteria, $changes);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        }

        return redirect()->route('bulk_editor.index')->with('info', 'Bulk change preview generated. Please use the interactive preview diff in the form.');
    }

    /**
     * Commit the bulk changes, queue channel synchronization outbox events, and record snapshot.
     */
    public function commit(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:5|max:255',
        ]);

        $roomTypeIds = $request->input('room_type_ids');
        if (!$roomTypeIds && $request->filled('room_type_id')) {
            $roomTypeIds = [(int) $request->input('room_type_id')];
        }
        if (!$roomTypeIds) {
            $roomTypeIds = RoomType::where('hotel_id', $hotelId)->pluck('id')->toArray();
        }

        $criteria = [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'room_type_ids' => $roomTypeIds,
        ];

        $changes = array_filter([
            'available_units' => $request->available_units,
            'base_rate' => $request->base_rate,
            'price_delta' => $request->price_delta,
            'stop_sell' => $request->has('stop_sell') ? (bool) $request->stop_sell : null,
            'min_stay' => $request->min_stay,
            'reason' => $request->reason,
        ], fn($v) => !is_null($v) && $v !== '');

        $batch = $this->inventoryService->applyBulkChange($hotelId, $criteria, $changes, Auth::id());

        return redirect()->route('bulk_editor.index')->with('success', "Bulk update committed successfully: {$batch->summary}. Synchronization outbox dispatched to connected channels.");
    }

    /**
     * One-click action to initialize or refresh 90-day demo inventory.
     */
    public function seedDemoInventory(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $created = $this->inventoryService->ensureUpcomingInventory($hotelId, 90);

        return redirect()->route('bulk_editor.index')->with('success', "Demonstration inventory refreshed: {$created} upcoming day-records ensured for active property.");
    }

    /**
     * Safe Rollback: Revert a previously applied batch.
     */
    public function rollback(Request $request, BulkChangeBatch|int $batch)
    {
        $batchId = is_int($batch) ? $batch : $batch->id;
        $success = $this->inventoryService->rollbackBatch($batchId, Auth::id());

        if ($success) {
            return redirect()->route('bulk_editor.index')->with('success', 'Batch changes were successfully rolled back and restored on all channels.');
        }

        return redirect()->route('bulk_editor.index')->with('error', 'Batch was already rolled back or could not be found.');
    }
}
