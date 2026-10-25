<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Models\User;
use App\Services\HousekeepingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HousekeepingController extends Controller
{
    protected HousekeepingService $housekeepingService;

    public function __construct(HousekeepingService $housekeepingService)
    {
        $this->housekeepingService = $housekeepingService;
    }

    public function index(Request $request)
    {
        $hotelId = session('current_hotel_id') ?? Auth::user()->hotel_id;
        $hotel = Hotel::find($hotelId);

        $status = $request->input('status');
        $priority = $request->input('priority');

        $query = HousekeepingTask::with(['room.roomType', 'assignee'])
            ->where('hotel_id', $hotelId);

        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        $tasks = $query->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $rooms = Room::where('hotel_id', $hotelId)->get();
        $staff = User::where('hotel_id', $hotelId)->whereIn('role', ['housekeeping', 'manager', 'frontdesk'])->get();

        $summary = [
            'pending' => HousekeepingTask::where('hotel_id', $hotelId)->where('status', 'pending')->count(),
            'in_progress' => HousekeepingTask::where('hotel_id', $hotelId)->where('status', 'in_progress')->count(),
            'completed_today' => HousekeepingTask::where('hotel_id', $hotelId)->where('status', 'completed')->whereDate('completed_at', today())->count(),
            'dirty_rooms' => Room::where('hotel_id', $hotelId)->where('status', 'dirty')->count(),
        ];

        return view('housekeeping.index', compact('hotel', 'tasks', 'rooms', 'staff', 'summary', 'status', 'priority'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'task_type' => 'required|in:checkout_turnover,daily_cleaning,deep_clean,inspection,maintenance',
            'priority' => 'required|in:low,normal,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $this->housekeepingService->createTask(
            $request->room_id,
            $request->task_type,
            $request->priority,
            $request->assigned_to,
            $request->notes
        );

        return back()->with('success', __('clearstay.housekeeping.task_created_success'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $this->housekeepingService->updateTaskStatus($id, $request->status);

        return back()->with('success', __('clearstay.housekeeping.task_updated_success'));
    }
}
