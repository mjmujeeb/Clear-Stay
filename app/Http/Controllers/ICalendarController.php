<?php

namespace App\Http\Controllers;

use App\Models\CentralInventory;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ICalendarController extends Controller
{
    /**
     * Export live RFC 5545 iCalendar feed for external calendar subscription.
     * Note: iCal is suitable for 1-way calendar sync, not full 2-way OTA distribution.
     */
    public function export(Request $request, Hotel $hotel, RoomType $roomType): Response
    {
        $startDate = Carbon::today()->format('Y-m-d');
        $endDate = Carbon::today()->addDays(90)->format('Y-m-d');

        $inventories = CentralInventory::where('hotel_id', $hotel->id)
            ->where('room_type_id', $roomType->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->orderBy('date')
            ->get();

        $reservations = Reservation::where('hotel_id', $hotel->id)
            ->where('room_type_id', $roomType->id)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_out_date', '>=', $startDate)
            ->whereDate('check_in_date', '<=', $endDate)
            ->get();

        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//ClearStay Hospitality//ClearStay 4.2 iCal Engine//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = "X-WR-CALNAME:ClearStay - {$hotel->name} [{$roomType->name}]";
        $lines[] = "X-WR-TIMEZONE:Asia/Tokyo";

        // 1. Add VEVENT entries for reservations (Blocked dates)
        foreach ($reservations as $res) {
            $dtStart = $res->check_in_date->format('Ymd');
            $dtEnd = $res->check_out_date->format('Ymd');
            $uid = "res-{$res->id}-" . md5($res->confirmation_code) . "@clearstay.jp";

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = "UID:{$uid}";
            $lines[] = "DTSTAMP:" . gmdate('Ymd\THis\Z');
            $lines[] = "DTSTART;VALUE=DATE:{$dtStart}";
            $lines[] = "DTEND;VALUE=DATE:{$dtEnd}";
            $lines[] = "SUMMARY:Reserved: {$res->confirmation_code} [{$roomType->name}]";
            $lines[] = "DESCRIPTION:ClearStay PMS Confirmed Booking\\nGuests: {$res->adults}\\nStatus: {$res->status}";
            $lines[] = "STATUS:CONFIRMED";
            $lines[] = "TRANSP:OPAQUE";
            $lines[] = 'END:VEVENT';
        }

        // 2. Add VEVENT entries for Stop-Sell / Fully Booked dates
        foreach ($inventories as $inv) {
            if ($inv->stop_sell || $inv->available_units <= 0) {
                $dt = $inv->date->format('Ymd');
                $nextDt = $inv->date->copy()->addDay()->format('Ymd');
                $uid = "closed-{$inv->id}-{$dt}@clearstay.jp";
                $reason = $inv->stop_sell ? 'Stop-Sell Active' : 'Zero Available Units';

                $lines[] = 'BEGIN:VEVENT';
                $lines[] = "UID:{$uid}";
                $lines[] = "DTSTAMP:" . gmdate('Ymd\THis\Z');
                $lines[] = "DTSTART;VALUE=DATE:{$dt}";
                $lines[] = "DTEND;VALUE=DATE:{$nextDt}";
                $lines[] = "SUMMARY:Closed to Booking: {$roomType->name} ({$reason})";
                $lines[] = "DESCRIPTION:Rate: JPY {$inv->base_rate}\\nAvailable Units: {$inv->available_units}";
                $lines[] = "STATUS:CONFIRMED";
                $lines[] = "TRANSP:OPAQUE";
                $lines[] = 'END:VEVENT';
            }
        }

        $lines[] = 'END:VCALENDAR';

        $icsContent = implode("\r\n", $lines) . "\r\n";
        $filename = "clearstay_{$hotel->code}_{$roomType->code}.ics";

        return response($icsContent, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
