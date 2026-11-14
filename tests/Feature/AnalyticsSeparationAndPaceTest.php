<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsSeparationAndPaceTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;
    protected RoomType $deluxeType;
    protected RoomType $standardType;
    protected Room $room1;
    protected Room $room2;
    protected Channel $bookingChannel;
    protected Channel $agodaChannel;
    protected Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Kyoto Ryokan',
            'slug' => 'clearstay-kyoto-ryokan',
            'code' => 'CS-KYO-RYO',
            'address' => 'Higashiyama-ku',
            'city' => 'Kyoto',
            'country' => 'JP',
            'total_rooms' => 10,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Hiroshi Tanaka',
            'email' => 'hiroshi.tanaka@clearstay.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'department' => 'Executive',
            'locale' => 'ja',
            'is_active' => true,
        ]);

        $this->deluxeType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe Suite',
            'code' => 'DLX-STE',
            'base_price' => 35000,
            'capacity' => 2,
            'total_rooms' => 5,
        ]);

        $this->standardType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Standard Double',
            'code' => 'STD-DBL',
            'base_price' => 18000,
            'capacity' => 2,
            'total_rooms' => 5,
        ]);

        $this->room1 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->deluxeType->id,
            'room_number' => '301',
            'floor' => 3,
            'status' => 'clean',
            'is_occupied' => false,
        ]);

        $this->room2 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->standardType->id,
            'room_number' => '102',
            'floor' => 1,
            'status' => 'dirty',
            'is_occupied' => false,
        ]);

        $this->bookingChannel = Channel::create([
            'name' => 'Booking.com',
            'code' => 'BOOKING_COM',
            'is_active' => true,
        ]);

        $this->agodaChannel = Channel::create([
            'name' => 'Agoda',
            'code' => 'AGODA',
            'is_active' => true,
        ]);

        $this->guest = Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Kenzo',
            'last_name' => 'Mori',
            'email' => 'kenzo.mori@example.jp',
            'phone' => '+81-90-7777-8888',
        ]);
    }

    /**
     * Test 1: Booking Analytics Plus renders dedicated view and pure booking behavior metrics
     */
    public function test_booking_analytics_renders_dedicated_view_with_booking_behavior_metrics(): void
    {
        // Create sample reservation with 5-day lead time
        $res = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room1->id,
            'room_type_id' => $this->deluxeType->id,
            'channel_id' => $this->bookingChannel->id,
            'confirmation_code' => 'CS-BA-TEST-1',
            'check_in_date' => Carbon::today()->addDays(5)->toDateString(),
            'check_out_date' => Carbon::today()->addDays(8)->toDateString(),
            'status' => 'confirmed',
            'total_price' => 105000,
            'nights' => 3,
            'adults' => 2,
            'children' => 0,
            'created_at' => Carbon::today(),
        ]);

        $response = $this->actingAs($this->manager)->get(route('booking_analytics.index'));

        $response->assertOk();
        // Assert view is strictly booking_analytics.index (NOT analytics.index)
        $this->assertEquals('booking_analytics.index', $response->original->name());

        // Assert view has all booking-specific datasets
        $response->assertViewHas('metrics');
        $response->assertViewHas('historicalComparison');
        $response->assertViewHas('bookingPace');
        $response->assertViewHas('leadTimeDistribution');
        $response->assertViewHas('lengthOfStayDistribution');
        $response->assertViewHas('futureOnTheBooks');
        $response->assertViewHas('channelDistribution');

        // Check metrics structure
        $metrics = $response->viewData('metrics');
        $this->assertArrayHasKey('avg_lead_time', $metrics);
        $this->assertArrayHasKey('avg_length_of_stay', $metrics);
        $this->assertArrayHasKey('net_pickup_7d', $metrics);
        $this->assertArrayHasKey('future_room_nights_otb', $metrics);
        $this->assertGreaterThanOrEqual(1, $metrics['rooms_sold']);
    }

    /**
     * Test 2: Hotel Analytics renders dedicated view with commercial commission deductions and operations
     */
    public function test_hotel_analytics_renders_dedicated_view_with_financial_and_commission_metrics(): void
    {
        // Create 2 reservations: one through Booking.com (15% commission) and one Direct (0% commission)
        Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room1->id,
            'room_type_id' => $this->deluxeType->id,
            'channel_id' => $this->bookingChannel->id,
            'confirmation_code' => 'CS-FIN-BK',
            'check_in_date' => Carbon::now()->startOfMonth()->toDateString(),
            'check_out_date' => Carbon::now()->startOfMonth()->addDays(2)->toDateString(),
            'status' => 'checked_out',
            'total_price' => 70000, // 15% commission = 10,500; Net = 59,500
            'nights' => 2,
            'adults' => 2,
            'children' => 0,
        ]);

        Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room2->id,
            'room_type_id' => $this->standardType->id,
            'channel_id' => null, // Direct Web (0% commission)
            'confirmation_code' => 'CS-FIN-DIR',
            'check_in_date' => Carbon::now()->startOfMonth()->toDateString(),
            'check_out_date' => Carbon::now()->startOfMonth()->addDays(1)->toDateString(),
            'status' => 'checked_out',
            'total_price' => 18000, // 0% commission; Net = 18,000
            'nights' => 1,
            'adults' => 1,
            'children' => 0,
        ]);

        $response = $this->actingAs($this->manager)->get(route('analytics.index'));

        $response->assertOk();
        // Assert view is analytics.index
        $this->assertEquals('analytics.index', $response->original->name());

        $response->assertViewHas('kpis');
        $response->assertViewHas('channelProfitability');
        $response->assertViewHas('operations');
        $response->assertViewHas('roomTypePerformance');
        $response->assertViewHas('executiveInsights');

        $kpis = $response->viewData('kpis');
        $this->assertEquals(88000, $kpis['gross_revenue']);
        $this->assertEquals(10500, $kpis['commission_expense']);
        $this->assertEquals(77500, $kpis['net_revenue']);

        $profitability = $response->viewData('channelProfitability');
        $this->assertNotEmpty($profitability);

        $operations = $response->viewData('operations');
        $this->assertArrayHasKey('readiness_score', $operations);
    }

    /**
     * Test 3: Hotel Analytics JSON endpoints operate properly
     */
    public function test_hotel_analytics_json_endpoints(): void
    {
        // 1. Occupancy data
        $occResponse = $this->actingAs($this->manager)->get(route('analytics.occupancy', ['days_back' => 3, 'days_forward' => 3]));
        $occResponse->assertOk();
        $occJson = $occResponse->json();
        $this->assertArrayHasKey('labels', $occJson);
        $this->assertArrayHasKey('occupancy_percentages', $occJson);

        // 2. Revenue data
        $revResponse = $this->actingAs($this->manager)->get(route('analytics.revenue', ['months' => 4]));
        $revResponse->assertOk();
        $revJson = $revResponse->json();
        $this->assertArrayHasKey('revenue', $revJson);
        $this->assertCount(4, $revJson['revenue']);

        // 3. Distribution data
        $distResponse = $this->actingAs($this->manager)->get(route('analytics.distribution'));
        $distResponse->assertOk();
        $distJson = $distResponse->json();
        $this->assertArrayHasKey('labels', $distJson);

        // 4. Profitability data
        $profResponse = $this->actingAs($this->manager)->get(route('analytics.profitability'));
        $profResponse->assertOk();
        $this->assertIsArray($profResponse->json());
    }

    /**
     * Test 4: Reports Studio and CSV Import remain strictly distinct routes
     */
    public function test_reports_studio_and_csv_import_remain_distinct(): void
    {
        $reportsResponse = $this->actingAs($this->manager)->get(route('reports.index'));
        $reportsResponse->assertOk();
        $this->assertEquals('reports.index', $reportsResponse->original->name());

        $csvResponse = $this->actingAs($this->manager)->get(route('csv.index'));
        $csvResponse->assertOk();
        $this->assertEquals('csv.index', $csvResponse->original->name());
    }
}
