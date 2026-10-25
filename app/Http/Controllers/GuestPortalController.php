<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use Illuminate\Http\Request;

class GuestPortalController extends Controller
{
    /**
     * Public self-service portal for guests using secure token.
     */
    public function show(string $token)
    {
        $reservation = Reservation::withoutGlobalScopes()
            ->with(['hotel', 'room.roomType', 'guest'])
            ->where('guest_portal_token', $token)
            ->firstOrFail();

        return view('guest.portal', compact('reservation'));
    }

    /**
     * Submit guest request (extra amenities, housekeeping, luggage).
     */
    public function submitRequest(Request $request, string $token)
    {
        $reservation = Reservation::withoutGlobalScopes()
            ->with(['hotel', 'room'])
            ->where('guest_portal_token', $token)
            ->firstOrFail();

        $request->validate([
            'request_type' => 'required|in:extra_towels,extra_pillows,cleaning_time,luggage_drop,other',
            'details' => 'nullable|string|max:500',
        ]);

        $taskType = $request->request_type === 'cleaning_time' ? 'daily_cleaning' : 'daily_cleaning';
        $priority = in_array($request->request_type, ['extra_towels', 'extra_pillows']) ? 'normal' : 'low';

        $typeLabels = [
            'extra_towels' => 'Extra Towels Requested',
            'extra_pillows' => 'Extra Pillows Requested',
            'cleaning_time' => 'Preferred Cleaning Time',
            'luggage_drop' => 'Early Luggage Drop-off',
            'other' => 'Guest Special Request',
        ];

        $note = "GUEST PORTAL REQUEST: " . ($typeLabels[$request->request_type] ?? 'Special Request');
        if ($request->details) {
            $note .= " - " . $request->details;
        }

        HousekeepingTask::create([
            'hotel_id' => $reservation->hotel_id,
            'room_id' => $reservation->room_id,
            'task_type' => 'daily_cleaning',
            'priority' => $priority,
            'status' => 'pending',
            'notes' => $note,
        ]);

        AuditLog::record('guest.portal_request', $reservation, null, [
            'request' => $request->request_type,
            'details' => $request->details,
        ]);

        return back()->with('success', __('clearstay.guest_portal.request_submitted'));
    }
}
