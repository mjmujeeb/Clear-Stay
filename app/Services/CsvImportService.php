<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CsvImportService
{
    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Import reservations and guests from a CSV file.
     *
     * Expected CSV headers:
     * first_name,last_name,email,phone,room_number,check_in_date,check_out_date,adults,children,total_price,special_requests
     */
    public function importReservationsFromCsv(string $filePath, int $hotelId, ?int $userId = null): array
    {
        $hotel = Hotel::findOrFail($hotelId);

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'success' => false,
                'imported' => 0,
                'failed' => 0,
                'errors' => ['File could not be opened or does not exist.'],
                'records' => [],
            ];
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [
                'success' => false,
                'imported' => 0,
                'failed' => 0,
                'errors' => ['Unable to read CSV file.'],
                'records' => [],
            ];
        }

        // Read and sanitize headers
        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            fclose($handle);
            return [
                'success' => false,
                'imported' => 0,
                'failed' => 0,
                'errors' => ['CSV file is empty.'],
                'records' => [],
            ];
        }

        $headers = array_map(function ($h) {
            return trim(strtolower(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
        }, $rawHeaders);

        $requiredHeaders = ['first_name', 'last_name', 'email', 'room_number', 'check_in_date', 'check_out_date'];
        $missing = array_diff($requiredHeaders, $headers);
        if (!empty($missing)) {
            fclose($handle);
            return [
                'success' => false,
                'imported' => 0,
                'failed' => 0,
                'errors' => ['Missing required CSV headers: ' . implode(', ', $missing)],
                'records' => [],
            ];
        }

        $imported = 0;
        $failed = 0;
        $errors = [];
        $records = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            if (empty(array_filter($row))) {
                continue; // Skip empty lines
            }

            if (count($row) < count($headers)) {
                $failed++;
                $errors[] = "Line {$lineNumber}: Incomplete column count.";
                continue;
            }

            $data = array_combine(array_slice($headers, 0, count($row)), $row);

            // Validate row data
            $validator = Validator::make($data, [
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|max:150',
                'phone' => 'nullable|string|max:30',
                'room_number' => 'required|string',
                'check_in_date' => 'required|date',
                'check_out_date' => 'required|date|after:check_in_date',
                'adults' => 'nullable|integer|min:1',
                'children' => 'nullable|integer|min:0',
                'total_price' => 'nullable|numeric|min:0',
                'special_requests' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                $failed++;
                $errors[] = "Line {$lineNumber}: " . implode(' ', $validator->errors()->all());
                continue;
            }

            try {
                DB::transaction(function () use ($data, $hotel, $userId, &$records, &$imported) {
                    // Find or create guest for this hotel
                    $guest = Guest::withoutGlobalScopes()->firstOrCreate([
                        'hotel_id' => $hotel->id,
                        'email' => trim($data['email']),
                    ], [
                        'first_name' => trim($data['first_name']),
                        'last_name' => trim($data['last_name']),
                        'phone' => trim($data['phone'] ?? ''),
                        'vip_level' => 'standard',
                    ]);

                    // Find room in this hotel
                    $room = Room::withoutGlobalScopes()
                        ->where('hotel_id', $hotel->id)
                        ->where('room_number', trim($data['room_number']))
                        ->first();

                    if (!$room) {
                        throw new \Exception("Room number '{$data['room_number']}' not found in hotel '{$hotel->name}'.");
                    }

                    $checkIn = Carbon::parse($data['check_in_date'])->toDateString();
                    $checkOut = Carbon::parse($data['check_out_date'])->toDateString();

                    // Check room conflict with lock
                    $conflict = Reservation::withoutGlobalScopes()
                        ->where('room_id', $room->id)
                        ->whereIn('status', ['confirmed', 'checked_in'])
                        ->where('check_in_date', '<', $checkOut)
                        ->where('check_out_date', '>', $checkIn)
                        ->lockForUpdate()
                        ->first();

                    if ($conflict) {
                        throw new \Exception("Room '{$room->room_number}' is already booked from {$conflict->check_in_date->toDateString()} to {$conflict->check_out_date->toDateString()} (Code: {$conflict->confirmation_code}).");
                    }

                    $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)) ?: 1;
                    $totalPrice = !empty($data['total_price']) ? floatval($data['total_price']) : ($nights * $room->roomType->base_price);

                    $reservation = Reservation::create([
                        'hotel_id' => $hotel->id,
                        'guest_id' => $guest->id,
                        'room_id' => $room->id,
                        'check_in_date' => $checkIn,
                        'check_out_date' => $checkOut,
                        'adults' => !empty($data['adults']) ? intval($data['adults']) : 1,
                        'children' => !empty($data['children']) ? intval($data['children']) : 0,
                        'total_price' => $totalPrice,
                        'payment_status' => 'unpaid',
                        'status' => 'confirmed',
                        'special_requests' => $data['special_requests'] ?? null,
                        'source' => 'csv_import',
                    ]);

                    $records[] = [
                        'code' => $reservation->confirmation_code,
                        'guest' => $guest->full_name,
                        'room' => $room->room_number,
                        'dates' => "$checkIn to $checkOut",
                    ];
                    $imported++;
                });
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Line {$lineNumber} (" . ($data['email'] ?? 'Unknown') . "): " . $e->getMessage();
            }
        }

        fclose($handle);

        AuditLog::record('csv.import', null, null, [
            'hotel_id' => $hotelId,
            'imported' => $imported,
            'failed' => $failed,
            'total_processed' => $lineNumber - 1,
        ]);

        return [
            'success' => $imported > 0,
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
            'records' => $records,
        ];
    }

    /**
     * Generate sample CSV template content.
     */
    public static function getSampleCsv(): string
    {
        $csv = "first_name,last_name,email,phone,room_number,check_in_date,check_out_date,adults,children,total_price,special_requests\n";
        $csv .= "Taro,Yamada,taro.yamada@example.jp,+81-90-1234-5678,101,2026-11-01,2026-11-04,2,0,45000,Quiet room on upper floor\n";
        $csv .= "Hanako,Sato,hanako.sato@example.jp,+81-80-9876-5432,201,2026-11-05,2026-11-08,1,0,36000,Late check-in at 20:00\n";
        $csv .= "Michael,Smith,michael.smith@example.com,+1-415-555-0199,301,2026-11-10,2026-11-15,2,1,125000,Airport shuttle required\n";
        return $csv;
    }
}
