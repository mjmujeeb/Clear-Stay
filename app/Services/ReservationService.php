<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Reservation;
use App\Models\ReservationChange;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    /**
     * Change reservation dates or room safely using database transaction with row-level locking.
     * Prevents race conditions and double-bookings under concurrent requests.
     *
     * @throws ValidationException
     */
    public function changeDatesWithRowLock(
        int $reservationId,
        string $newCheckInDate,
        string $newCheckOutDate,
        ?int $newRoomId = null,
        ?string $reason = null,
        ?int $userId = null
    ): Reservation {
        $checkIn = Carbon::parse($newCheckInDate)->toDateString();
        $checkOut = Carbon::parse($newCheckOutDate)->toDateString();

        if ($checkIn >= $checkOut) {
            throw ValidationException::withMessages([
                'check_out_date' => [__('clearstay.errors.checkout_after_checkin')],
            ]);
        }

        return DB::transaction(function () use ($reservationId, $checkIn, $checkOut, $newRoomId, $reason, $userId) {
            // 1. Lock the reservation row for update
            $reservation = Reservation::withoutGlobalScopes()
                ->where('id', $reservationId)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($reservation->status, ['checked_out', 'cancelled'])) {
                throw ValidationException::withMessages([
                    'status' => [__('clearstay.errors.cannot_change_completed_reservation')],
                ]);
            }

            $targetRoomId = $newRoomId ?: $reservation->room_id;

            // 2. Lock the target room row to serialize competing allocations
            $room = Room::withoutGlobalScopes()
                ->where('id', $targetRoomId)
                ->lockForUpdate()
                ->firstOrFail();

            // 3. Check for overlapping active reservations on the target room using FOR UPDATE
            $conflict = Reservation::withoutGlobalScopes()
                ->where('room_id', $targetRoomId)
                ->where('id', '!=', $reservation->id)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in_date', '<', $checkOut)
                ->where('check_out_date', '>', $checkIn)
                ->lockForUpdate()
                ->first();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'room_id' => [__('clearstay.errors.room_unavailable_for_dates', [
                        'room' => $room->room_number,
                        'code' => $conflict->confirmation_code
                    ])],
                ]);
            }

            // Capture old values for audit
            $oldCheckIn = $reservation->check_in_date->toDateString();
            $oldCheckOut = $reservation->check_out_date->toDateString();
            $oldRoomId = $reservation->room_id;
            $oldTotalPrice = $reservation->total_price;

            // Recalculate price if room or dates changed
            $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)) ?: 1;
            $newTotalPrice = $nights * $room->roomType->base_price;

            // Update reservation
            $reservation->check_in_date = $checkIn;
            $reservation->check_out_date = $checkOut;
            $reservation->room_id = $targetRoomId;
            $reservation->total_price = $newTotalPrice;
            $reservation->save();

            // Record date change history
            ReservationChange::create([
                'reservation_id' => $reservation->id,
                'hotel_id' => $reservation->hotel_id,
                'user_id' => $userId,
                'old_check_in_date' => $oldCheckIn,
                'old_check_out_date' => $oldCheckOut,
                'new_check_in_date' => $checkIn,
                'new_check_out_date' => $checkOut,
                'old_room_id' => $oldRoomId,
                'new_room_id' => $targetRoomId,
                'old_total_price' => $oldTotalPrice,
                'new_total_price' => $newTotalPrice,
                'reason' => $reason ?: 'Guest date modification (Row locked transaction)',
                'ip_address' => Request::ip(),
            ]);

            // Audit log
            AuditLog::record('reservation.date_change', $reservation, [
                'check_in_date' => $oldCheckIn,
                'check_out_date' => $oldCheckOut,
                'room_id' => $oldRoomId,
                'total_price' => $oldTotalPrice,
            ], [
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'room_id' => $targetRoomId,
                'total_price' => $newTotalPrice,
                'reason' => $reason,
            ]);

            return $reservation->load(['room.roomType', 'guest']);
        });
    }

    /**
     * Create a new reservation with transactional availability check.
     */
    public function createReservation(array $data, ?int $userId = null): Reservation
    {
        $checkIn = Carbon::parse($data['check_in_date'])->toDateString();
        $checkOut = Carbon::parse($data['check_out_date'])->toDateString();

        if ($checkIn >= $checkOut) {
            throw ValidationException::withMessages([
                'check_out_date' => [__('clearstay.errors.checkout_after_checkin')],
            ]);
        }

        return DB::transaction(function () use ($data, $checkIn, $checkOut, $userId) {
            // Lock room row
            $room = Room::withoutGlobalScopes()
                ->where('id', $data['room_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Check availability with lock
            $conflict = Reservation::withoutGlobalScopes()
                ->where('room_id', $room->id)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in_date', '<', $checkOut)
                ->where('check_out_date', '>', $checkIn)
                ->lockForUpdate()
                ->first();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'room_id' => [__('clearstay.errors.room_unavailable_for_dates', [
                        'room' => $room->room_number,
                        'code' => $conflict->confirmation_code
                    ])],
                ]);
            }

            $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)) ?: 1;
            $totalPrice = $data['total_price'] ?? ($nights * $room->roomType->base_price);

            $reservation = Reservation::create([
                'hotel_id' => $data['hotel_id'] ?? $room->hotel_id,
                'guest_id' => $data['guest_id'],
                'room_id' => $room->id,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'adults' => $data['adults'] ?? 1,
                'children' => $data['children'] ?? 0,
                'total_price' => $totalPrice,
                'payment_status' => $data['payment_status'] ?? 'unpaid',
                'status' => 'confirmed',
                'special_requests' => $data['special_requests'] ?? null,
                'source' => $data['source'] ?? 'direct',
            ]);

            AuditLog::record('reservation.create', $reservation, null, [
                'confirmation_code' => $reservation->confirmation_code,
                'room' => $room->room_number,
                'dates' => "$checkIn ~ $checkOut",
            ]);

            return $reservation->load(['room.roomType', 'guest']);
        });
    }

    /**
     * Check in a guest and update room status.
     */
    public function checkIn(int $reservationId): Reservation
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = Reservation::lockForUpdate()->findOrFail($reservationId);
            $room = Room::lockForUpdate()->findOrFail($reservation->room_id);

            $reservation->status = 'checked_in';
            $reservation->checked_in_at = now();
            $reservation->save();

            $room->is_occupied = true;
            $room->save();

            AuditLog::record('reservation.check_in', $reservation);

            return $reservation;
        });
    }

    /**
     * Check out a guest, free the room, and mark room as dirty for housekeeping.
     */
    public function checkOut(int $reservationId): Reservation
    {
        return DB::transaction(function () use ($reservationId) {
            $reservation = Reservation::lockForUpdate()->findOrFail($reservationId);
            $room = Room::lockForUpdate()->findOrFail($reservation->room_id);

            $reservation->status = 'checked_out';
            $reservation->checked_out_at = now();
            $reservation->save();

            $room->is_occupied = false;
            $room->status = 'dirty'; // triggers housekeeping
            $room->save();

            // Create automatic housekeeping task
            $room->housekeepingTasks()->create([
                'hotel_id' => $room->hotel_id,
                'task_type' => 'checkout_turnover',
                'priority' => 'high',
                'status' => 'pending',
                'notes' => __('clearstay.housekeeping.turnover_note', ['code' => $reservation->confirmation_code]),
            ]);

            AuditLog::record('reservation.check_out', $reservation);

            return $reservation;
        });
    }

    /**
     * Cancel reservation.
     */
    public function cancel(int $reservationId, ?string $reason = null): Reservation
    {
        return DB::transaction(function () use ($reservationId, $reason) {
            $reservation = Reservation::lockForUpdate()->findOrFail($reservationId);
            $reservation->status = 'cancelled';
            $reservation->cancelled_at = now();
            $reservation->save();

            AuditLog::record('reservation.cancel', $reservation, null, ['reason' => $reason]);

            return $reservation;
        });
    }
}
