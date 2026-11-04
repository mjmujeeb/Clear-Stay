<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\HousekeepingTask;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class HousekeepingService
{
    /**
     * Fast room status update (Clean, Dirty, Inspecting, Maintenance).
     */
    public function updateRoomStatus(int $roomId, string $newStatus, ?string $notes = null): Room
    {
        return DB::transaction(function () use ($roomId, $newStatus, $notes) {
            $room = Room::lockForUpdate()->findOrFail($roomId);
            $oldStatus = $room->status;

            $room->status = $newStatus;
            if ($notes) {
                $room->notes = $notes;
            }
            $room->save();

            // If room changed to clean, complete any pending housekeeping tasks
            if ($newStatus === 'clean') {
                HousekeepingTask::where('room_id', $room->id)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
            }

            AuditLog::record('room.status_change', $room, ['status' => $oldStatus], ['status' => $newStatus, 'notes' => $notes]);

            return $room;
        });
    }

    /**
     * Create a new housekeeping task.
     */
    public function createTask(
        int $roomId,
        string $taskType = 'daily_cleaning',
        string $priority = 'normal',
        ?int $assignedTo = null,
        ?string $notes = null
    ): HousekeepingTask {
        return DB::transaction(function () use ($roomId, $taskType, $priority, $assignedTo, $notes) {
            $room = Room::findOrFail($roomId);

            $task = HousekeepingTask::create([
                'hotel_id' => $room->hotel_id,
                'room_id' => $room->id,
                'assigned_to' => $assignedTo,
                'task_type' => $taskType,
                'priority' => $priority,
                'status' => 'pending',
                'notes' => $notes,
            ]);

            AuditLog::record('housekeeping.task_create', $task);

            return $task;
        });
    }

    /**
     * Update task status (pending -> in_progress -> completed).
     */
    public function updateTaskStatus(int $taskId, string $status): HousekeepingTask
    {
        return DB::transaction(function () use ($taskId, $status) {
            $task = HousekeepingTask::lockForUpdate()->findOrFail($taskId);
            $oldStatus = $task->status;

            $task->status = $status;
            if ($status === 'in_progress' && !$task->started_at) {
                $task->started_at = now();
            }
            if ($status === 'completed') {
                $task->completed_at = now();
                // When turnover task completes, room becomes clean
                $room = Room::find($task->room_id);
                if ($room) {
                    $room->status = 'clean';
                    $room->save();
                }
            }
            $task->save();

            AuditLog::record('housekeeping.status_change', $task, ['status' => $oldStatus], ['status' => $status]);

            return $task;
        });
    }
}
