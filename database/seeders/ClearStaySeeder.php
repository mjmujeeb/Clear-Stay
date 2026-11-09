<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClearStaySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Hotels (Idempotent)
        $hotelTokyo = Hotel::firstOrCreate(['code' => 'TYO-GNZ'], [
            'name' => 'ClearStay Premier Tokyo Ginza',
            'slug' => 'clearstay-premier-tokyo-ginza',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'phone' => '+81-3-5555-0101',
            'email' => 'ginza@clearstay.jp',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'default_locale' => 'ja',
            'check_in_time' => '15:00:00',
            'check_out_time' => '11:00:00',
            'total_rooms' => 18,
            'is_active' => true,
        ]);

        $hotelKyoto = Hotel::firstOrCreate(['code' => 'KYO-GIO'], [
            'name' => 'ClearStay Kyoto Gion Heritage',
            'slug' => 'clearstay-kyoto-gion-heritage',
            'address' => '570 Gionmachi Minamigawa, Higashiyama-ku',
            'city' => 'Kyoto',
            'country' => 'JP',
            'phone' => '+81-75-555-0202',
            'email' => 'gion@clearstay.jp',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'default_locale' => 'ja',
            'check_in_time' => '15:00:00',
            'check_out_time' => '10:00:00',
            'total_rooms' => 12,
            'is_active' => true,
        ]);

        $hotelOsaka = Hotel::firstOrCreate(['code' => 'OSA-NMB'], [
            'name' => 'ClearStay Urban Osaka Namba',
            'slug' => 'clearstay-urban-osaka-namba',
            'address' => '1-10 Namba, Chuo-ku',
            'city' => 'Osaka',
            'country' => 'JP',
            'phone' => '+81-6-5555-0303',
            'email' => 'namba@clearstay.jp',
            'currency' => 'JPY',
            'timezone' => 'Asia/Tokyo',
            'default_locale' => 'ja',
            'check_in_time' => '15:00:00',
            'check_out_time' => '11:00:00',
            'total_rooms' => 15,
            'is_active' => true,
        ]);

        // 2. Create Users (RBAC) (Idempotent)
        $superAdmin = User::firstOrCreate(['email' => 'admin@clearstay.jp'], [
            'name' => 'ClearStay HQ Super Admin',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'hotel_id' => null,
            'locale' => 'en',
            'phone' => '+81-3-0000-0001',
            'is_active' => true,
        ]);

        $managerTokyo = User::firstOrCreate(['email' => 'manager.tokyo@clearstay.jp'], [
            'name' => 'Kenji Sato (General Manager)',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'hotel_id' => $hotelTokyo->id,
            'locale' => 'ja',
            'phone' => '+81-90-1111-2222',
            'is_active' => true,
        ]);

        $frontDeskTokyo = User::firstOrCreate(['email' => 'frontdesk.tokyo@clearstay.jp'], [
            'name' => 'Yuki Tanaka (Front Desk)',
            'password' => Hash::make('password'),
            'role' => 'frontdesk',
            'hotel_id' => $hotelTokyo->id,
            'locale' => 'en',
            'phone' => '+81-90-3333-4444',
            'is_active' => true,
        ]);

        $cleanerTokyo = User::firstOrCreate(['email' => 'cleaner.tokyo@clearstay.jp'], [
            'name' => 'Hiroshi Suzuki (Housekeeping Lead)',
            'password' => Hash::make('password'),
            'role' => 'housekeeping',
            'hotel_id' => $hotelTokyo->id,
            'locale' => 'ja',
            'phone' => '+81-90-5555-6666',
            'is_active' => true,
        ]);

        // 3. Create Room Types for Tokyo
        $typeSingle = RoomType::firstOrCreate(['hotel_id' => $hotelTokyo->id, 'code' => 'SGL-MOD'], [
            'name' => 'Modern Compact Single',
            'capacity' => 1,
            'base_price' => 13500,
            'description' => 'Ergonomic Japanese design optimized for business travelers, with soundproof glazing and high-speed fiber internet.',
            'amenities' => ['WiFi', 'Air Conditioning', 'Work Desk', 'Rain Shower', 'Yukata Robes'],
        ]);

        $typeDouble = RoomType::firstOrCreate(['hotel_id' => $hotelTokyo->id, 'code' => 'DBL-QUEEN'], [
            'name' => 'Superior Queen Double',
            'capacity' => 2,
            'base_price' => 21000,
            'description' => 'Spacious queen-bed suite with Simmons mattress, Nespresso machine, and panoramic city views.',
            'amenities' => ['WiFi', 'Air Conditioning', 'Bathtub', 'Nespresso', 'Minibar', 'City View'],
        ]);

        $typeTwin = RoomType::firstOrCreate(['hotel_id' => $hotelTokyo->id, 'code' => 'TWN-DLX'], [
            'name' => 'Deluxe Executive Twin',
            'capacity' => 3,
            'base_price' => 29500,
            'description' => 'Two semi-double beds with sitting lounge, Dyson hair dryer, and deep soaking tub.',
            'amenities' => ['WiFi', 'Sitting Lounge', 'Deep Soaking Tub', 'Air Purifier', 'Smart TV'],
        ]);

        $typeTatami = RoomType::firstOrCreate(['hotel_id' => $hotelTokyo->id, 'code' => 'SUI-TTM'], [
            'name' => 'Presidential Tatami Suite',
            'capacity' => 4,
            'base_price' => 58000,
            'description' => 'Authentic natural rush tatami flooring with hinoki wood bath and custom futon bedding.',
            'amenities' => ['WiFi', 'Hinoki Cypress Bath', 'Tea Ceremony Set', 'Shoji Screens', 'Garden Balcony'],
        ]);

        // 4. Create Rooms across floors 1 to 4 for Tokyo
        $roomsData = [
            ['floor' => 1, 'number' => '101', 'type' => $typeSingle, 'status' => 'clean', 'occ' => false],
            ['floor' => 1, 'number' => '102', 'type' => $typeSingle, 'status' => 'clean', 'occ' => false],
            ['floor' => 1, 'number' => '103', 'type' => $typeDouble, 'status' => 'dirty', 'occ' => false],
            ['floor' => 1, 'number' => '104', 'type' => $typeDouble, 'status' => 'clean', 'occ' => false],
            ['floor' => 2, 'number' => '201', 'type' => $typeSingle, 'status' => 'clean', 'occ' => true],
            ['floor' => 2, 'number' => '202', 'type' => $typeDouble, 'status' => 'clean', 'occ' => true],
            ['floor' => 2, 'number' => '203', 'type' => $typeDouble, 'status' => 'clean', 'occ' => false],
            ['floor' => 2, 'number' => '204', 'type' => $typeTwin, 'status' => 'inspecting', 'occ' => false],
            ['floor' => 3, 'number' => '301', 'type' => $typeDouble, 'status' => 'clean', 'occ' => true],
            ['floor' => 3, 'number' => '302', 'type' => $typeTwin, 'status' => 'clean', 'occ' => false],
            ['floor' => 3, 'number' => '303', 'type' => $typeTwin, 'status' => 'dirty', 'occ' => false],
            ['floor' => 3, 'number' => '304', 'type' => $typeTwin, 'status' => 'maintenance', 'occ' => false],
            ['floor' => 4, 'number' => '401', 'type' => $typeTwin, 'status' => 'clean', 'occ' => true],
            ['floor' => 4, 'number' => '402', 'type' => $typeTatami, 'status' => 'clean', 'occ' => true],
            ['floor' => 4, 'number' => '403', 'type' => $typeTatami, 'status' => 'clean', 'occ' => false],
        ];

        $createdRooms = [];
        foreach ($roomsData as $r) {
            $createdRooms[$r['number']] = Room::firstOrCreate(
                ['hotel_id' => $hotelTokyo->id, 'room_number' => $r['number']],
                [
                    'room_type_id' => $r['type']->id,
                    'floor' => $r['floor'],
                    'status' => $r['status'],
                    'is_occupied' => $r['occ'],
                ]
            );
        }

        // 5. Create Sample Guests
        $guestsData = [
            ['first' => 'Taro', 'last' => 'Yamada', 'email' => 'taro.yamada@example.jp', 'phone' => '+81-90-1234-5678', 'vip' => 'gold'],
            ['first' => 'Hanako', 'last' => 'Sato', 'email' => 'hanako.sato@example.jp', 'phone' => '+81-80-9876-5432', 'vip' => 'standard'],
            ['first' => 'Alexander', 'last' => 'Wright', 'email' => 'a.wright@example.co.uk', 'phone' => '+44-20-7946-0912', 'vip' => 'platinum'],
            ['first' => 'Mei-Ling', 'last' => 'Chen', 'email' => 'meiling.chen@example.tw', 'phone' => '+886-912-345-678', 'vip' => 'silver'],
            ['first' => 'Carlos', 'last' => 'Rodriguez', 'email' => 'carlos.r@example.es', 'phone' => '+34-600-123-456', 'vip' => 'standard'],
            ['first' => 'Sakura', 'last' => 'Watanabe', 'email' => 'sakura.w@example.jp', 'phone' => '+81-90-4444-8888', 'vip' => 'gold'],
        ];

        $createdGuests = [];
        foreach ($guestsData as $g) {
            $createdGuests[] = Guest::firstOrCreate(
                ['hotel_id' => $hotelTokyo->id, 'email' => $g['email']],
                [
                    'first_name' => $g['first'],
                    'last_name' => $g['last'],
                    'phone' => $g['phone'],
                    'vip_level' => $g['vip'],
                ]
            );
        }

        $today = Carbon::today();

        // 6. Active Occupied Stays (Idempotent)
        Reservation::firstOrCreate(['confirmation_code' => 'CS-TYO-1001'], [
            'hotel_id' => $hotelTokyo->id,
            'guest_id' => $createdGuests[0]->id,
            'room_id' => $createdRooms['201']->id,
            'guest_portal_token' => Str::random(48),
            'check_in_date' => $today->copy()->subDays(1)->toDateString(),
            'check_out_date' => $today->copy()->addDays(2)->toDateString(),
            'adults' => 1,
            'children' => 0,
            'total_price' => 13500 * 3,
            'payment_status' => 'paid',
            'status' => 'checked_in',
            'checked_in_at' => $today->copy()->subDays(1)->setHour(15),
            'source' => 'direct',
            'special_requests' => 'High floor, quiet corner, extra tea bags requested',
        ]);

        Reservation::firstOrCreate(['confirmation_code' => 'CS-TYO-1002'], [
            'hotel_id' => $hotelTokyo->id,
            'guest_id' => $createdGuests[1]->id,
            'room_id' => $createdRooms['202']->id,
            'guest_portal_token' => Str::random(48),
            'check_in_date' => $today->copy()->subDays(2)->toDateString(),
            'check_out_date' => $today->toDateString(), // Departing today!
            'adults' => 2,
            'children' => 0,
            'total_price' => 21000 * 2,
            'payment_status' => 'paid',
            'status' => 'checked_in',
            'checked_in_at' => $today->copy()->subDays(2)->setHour(16),
            'source' => 'direct',
        ]);

        Reservation::firstOrCreate(['confirmation_code' => 'CS-TYO-1003'], [
            'hotel_id' => $hotelTokyo->id,
            'guest_id' => $createdGuests[2]->id,
            'room_id' => $createdRooms['402']->id,
            'guest_portal_token' => Str::random(48),
            'check_in_date' => $today->copy()->subDays(1)->toDateString(),
            'check_out_date' => $today->copy()->addDays(3)->toDateString(),
            'adults' => 2,
            'children' => 1,
            'total_price' => 58000 * 4,
            'payment_status' => 'paid',
            'status' => 'checked_in',
            'checked_in_at' => $today->copy()->subDays(1)->setHour(14),
            'source' => 'direct',
            'special_requests' => 'VIP Platinum guest. Traditional matcha welcome set provided.',
        ]);

        // Today Arrival (Confirmed)
        Reservation::firstOrCreate(['confirmation_code' => 'CS-TYO-1004'], [
            'hotel_id' => $hotelTokyo->id,
            'guest_id' => $createdGuests[3]->id,
            'room_id' => $createdRooms['101']->id,
            'guest_portal_token' => Str::random(48),
            'check_in_date' => $today->toDateString(), // Arriving today!
            'check_out_date' => $today->copy()->addDays(3)->toDateString(),
            'adults' => 1,
            'children' => 0,
            'total_price' => 13500 * 3,
            'payment_status' => 'unpaid',
            'status' => 'confirmed',
            'source' => 'direct',
            'special_requests' => 'Arriving late at 19:30 from Narita Express',
        ]);

        Reservation::firstOrCreate(['confirmation_code' => 'CS-TYO-1005'], [
            'hotel_id' => $hotelTokyo->id,
            'guest_id' => $createdGuests[4]->id,
            'room_id' => $createdRooms['302']->id,
            'guest_portal_token' => Str::random(48),
            'check_in_date' => $today->toDateString(), // Arriving today!
            'check_out_date' => $today->copy()->addDays(2)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'total_price' => 29500 * 2,
            'payment_status' => 'unpaid',
            'status' => 'confirmed',
            'source' => 'direct',
        ]);

        // Historical reservations for past 6 months to populate Chart.js graphs beautifully
        for ($m = 5; $m >= 1; $m--) {
            for ($k = 1; $k <= 4; $k++) {
                $pastDate = $today->copy()->subMonths($m)->addDays($k * 5);
                Reservation::firstOrCreate(['confirmation_code' => 'CS-HIST-' . $m . $k], [
                    'hotel_id' => $hotelTokyo->id,
                    'guest_id' => $createdGuests[$k % count($createdGuests)]->id,
                    'room_id' => $createdRooms['104']->id,
                    'guest_portal_token' => Str::random(48),
                    'check_in_date' => $pastDate->toDateString(),
                    'check_out_date' => $pastDate->copy()->addDays(2)->toDateString(),
                    'adults' => 2,
                    'children' => 0,
                    'total_price' => 42000,
                    'payment_status' => 'paid',
                    'status' => 'checked_out',
                    'checked_in_at' => $pastDate->setHour(15),
                    'checked_out_at' => $pastDate->copy()->addDays(2)->setHour(10),
                    'source' => 'direct',
                ]);
            }
        }

        // 7. Housekeeping Tasks
        HousekeepingTask::firstOrCreate(['hotel_id' => $hotelTokyo->id, 'room_id' => $createdRooms['103']->id, 'task_type' => 'checkout_turnover'], [
            'assigned_to' => $cleanerTokyo->id,
            'priority' => 'urgent',
            'status' => 'pending',
            'notes' => 'Checkout turnover for 103. New guest arriving at 15:00.',
        ]);

        HousekeepingTask::firstOrCreate(['hotel_id' => $hotelTokyo->id, 'room_id' => $createdRooms['204']->id, 'task_type' => 'inspection'], [
            'assigned_to' => $cleanerTokyo->id,
            'priority' => 'high',
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(20),
            'notes' => 'Supervisor inspection after deep cleaning.',
        ]);

        HousekeepingTask::firstOrCreate(['hotel_id' => $hotelTokyo->id, 'room_id' => $createdRooms['304']->id, 'task_type' => 'maintenance'], [
            'assigned_to' => null,
            'priority' => 'normal',
            'status' => 'pending',
            'notes' => 'Inspect AC filter and bathroom thermostat display.',
        ]);

        // 8. Audit Logs
        AuditLog::create([
            'hotel_id' => $hotelTokyo->id,
            'user_id' => $superAdmin->id,
            'action' => 'system.initialized',
            'auditable_type' => Hotel::class,
            'auditable_id' => $hotelTokyo->id,
            'old_values' => null,
            'new_values' => ['name' => $hotelTokyo->name, 'status' => 'Active'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'ClearStay Setup Seeder',
        ]);
    }
}
