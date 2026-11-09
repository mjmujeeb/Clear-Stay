<?php

namespace App\Services\Inventory;

use App\Models\AuditLog;
use App\Models\BulkChangeBatch;
use App\Models\CentralInventory;
use App\Models\ChannelConnection;
use App\Models\RoomType;
use App\Services\Channel\ChannelSynchronizationService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CentralInventoryService
{
    protected ChannelSynchronizationService $syncService;

    public function __construct(ChannelSynchronizationService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Get or initialize central inventory records for a date range.
     */
    public function getInventoryMatrix(int $hotelId, ?int $roomTypeId, string $startDate, string $endDate): array
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $roomTypeQuery = RoomType::where('hotel_id', $hotelId);
        if ($roomTypeId) {
            $roomTypeQuery->where('id', $roomTypeId);
        }
        $roomTypes = $roomTypeQuery->get();

        $matrix = [];

        foreach ($roomTypes as $type) {
            $typeData = [
                'room_type' => $type,
                'dates' => [],
            ];

            foreach ($period as $date) {
                $dateStr = $date->toDateString();
                $record = CentralInventory::firstOrCreate(
                    [
                        'hotel_id' => $hotelId,
                        'room_type_id' => $type->id,
                        'date' => $dateStr,
                    ],
                    [
                        'total_units' => 10,
                        'available_units' => 10,
                        'reserved_units' => 0,
                        'maintenance_units' => 0,
                        'base_rate' => $type->base_price,
                        'currency' => 'JPY',
                        'min_stay' => 1,
                        'max_stay' => 30,
                        'stop_sell' => false,
                    ]
                );

                $typeData['dates'][$dateStr] = $record;
            }

            $matrix[$type->id] = $typeData;
        }

        return $matrix;
    }

    /**
     * Generate an impact preview before applying high-impact bulk changes.
     */
    public function generateBulkPreview(int $hotelId, array $criteria, array $changes): array
    {
        $startDate = $criteria['start_date'];
        $endDate = $criteria['end_date'];
        $roomTypeIds = $criteria['room_type_ids'] ?? [];

        $period = CarbonPeriod::create($startDate, $endDate);
        $daysCount = count($period);

        $connectedChannels = ChannelConnection::where('hotel_id', $hotelId)
            ->where('is_enabled', true)
            ->with('channel')
            ->get();

        $affectedItems = [];
        $query = CentralInventory::where('hotel_id', $hotelId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if (!empty($roomTypeIds)) {
            $query->whereIn('room_type_id', $roomTypeIds);
        }

        $currentRecords = $query->with('roomType')->get();

        foreach ($currentRecords as $rec) {
            $item = [
                'room_type' => $rec->roomType->name,
                'date' => $rec->date->toDateString(),
                'old_available' => $rec->available_units,
                'new_available' => isset($changes['available_units']) ? intval($changes['available_units']) : $rec->available_units,
                'old_rate' => $rec->base_rate,
                'new_rate' => isset($changes['base_rate']) ? floatval($changes['base_rate']) : $rec->base_rate,
                'old_stop_sell' => $rec->stop_sell,
                'new_stop_sell' => isset($changes['stop_sell']) ? (bool) $changes['stop_sell'] : $rec->stop_sell,
            ];
            $affectedItems[] = $item;
        }

        return [
            'total_days' => $daysCount,
            'total_records_affected' => count($affectedItems),
            'channels_count' => $connectedChannels->count(),
            'channels' => $connectedChannels->pluck('channel.name')->toArray(),
            'sample_items' => array_slice($affectedItems, 0, 10),
            'changes_summary' => $changes,
        ];
    }

    /**
     * Apply a bulk change, save rollback snapshot, and broadcast to all connected channels.
     */
    public function applyBulkChange(int $hotelId, array $criteria, array $changes, ?int $userId = null): BulkChangeBatch
    {
        $startDate = $criteria['start_date'];
        $endDate = $criteria['end_date'];
        $roomTypeIds = $criteria['room_type_ids'] ?? [];

        return DB::transaction(function () use ($hotelId, $criteria, $changes, $startDate, $endDate, $roomTypeIds, $userId) {
            $query = CentralInventory::where('hotel_id', $hotelId)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate);

            if (!empty($roomTypeIds)) {
                $query->whereIn('room_type_id', $roomTypeIds);
            }

            $records = $query->lockForUpdate()->get();
            $previousState = [];
            $newState = [];
            $syncItems = [];

            foreach ($records as $record) {
                $previousState[$record->id] = [
                    'available_units' => $record->available_units,
                    'base_rate' => $record->base_rate,
                    'stop_sell' => $record->stop_sell,
                    'min_stay' => $record->min_stay,
                ];

                if (isset($changes['available_units'])) {
                    $record->available_units = intval($changes['available_units']);
                }
                if (isset($changes['base_rate'])) {
                    $record->base_rate = floatval($changes['base_rate']);
                }
                if (isset($changes['stop_sell'])) {
                    $record->stop_sell = (bool) $changes['stop_sell'];
                }
                if (isset($changes['min_stay'])) {
                    $record->min_stay = intval($changes['min_stay']);
                }
                $record->version += 1;
                $record->save();

                $newState[$record->id] = [
                    'available_units' => $record->available_units,
                    'base_rate' => $record->base_rate,
                    'stop_sell' => $record->stop_sell,
                    'min_stay' => $record->min_stay,
                ];

                $syncItems[] = [
                    'room_type_id' => $record->room_type_id,
                    'date' => $record->date->toDateString(),
                    'available' => $record->available_units,
                    'rate' => $record->base_rate,
                    'stop_sell' => $record->stop_sell,
                ];
            }

            $batch = BulkChangeBatch::create([
                'hotel_id' => $hotelId,
                'user_id' => $userId ?? Auth::id(),
                'change_type' => 'bulk_inventory_rate_update',
                'summary' => "Updated " . count($records) . " inventory dates between {$startDate} and {$endDate}",
                'scope_criteria' => $criteria,
                'previous_state' => $previousState,
                'new_state' => $newState,
                'status' => 'applied',
            ]);

            // Broadcast to connected channels
            $this->syncService->broadcastInventoryChange($hotelId, $syncItems, 'bulk_update');

            return $batch;
        });
    }

    /**
     * Safe Rollback: Reverts a previously applied bulk change batch and re-synchronizes channels.
     */
    public function rollbackBatch(int $batchId, ?int $userId = null): bool
    {
        return DB::transaction(function () use ($batchId, $userId) {
            $batch = BulkChangeBatch::where('id', $batchId)->lockForUpdate()->firstOrFail();

            if ($batch->status === 'rolled_back') {
                return false;
            }

            $previousState = $batch->previous_state;
            $syncItems = [];

            foreach ($previousState as $recordId => $values) {
                $record = CentralInventory::find($recordId);
                if ($record) {
                    $record->available_units = $values['available_units'];
                    $record->base_rate = $values['base_rate'];
                    $record->stop_sell = $values['stop_sell'];
                    $record->min_stay = $values['min_stay'] ?? $record->min_stay;
                    $record->version += 1;
                    $record->save();

                    $syncItems[] = [
                        'room_type_id' => $record->room_type_id,
                        'date' => $record->date->toDateString(),
                        'available' => $record->available_units,
                        'rate' => $record->base_rate,
                        'stop_sell' => $record->stop_sell,
                    ];
                }
            }

            $batch->update([
                'status' => 'rolled_back',
                'rolled_back_at' => now(),
                'rolled_back_by' => $userId ?? Auth::id(),
            ]);

            AuditLog::record('inventory.bulk_rollback', null, null, [
                'batch_id' => $batchId,
                'hotel_id' => $batch->hotel_id,
            ]);

            // Synchronize restored inventory
            $this->syncService->broadcastInventoryChange($batch->hotel_id, $syncItems, 'rollback_sync');

            return true;
        });
    }
}
