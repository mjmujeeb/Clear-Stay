<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Hotel;
use App\Models\HotelPricingSetting;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Pricing\ReservationPricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ReservationPricingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected RoomType $standardRoomType;
    protected Room $room101;
    protected ReservationPricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Ginza Test',
            'slug' => 'clearstay-ginza-test',
            'code' => 'CS-GNZ',
            'address' => 'Ginza 6-chome',
            'city' => 'Tokyo',
            'country' => 'JP',
            'total_rooms' => 5,
            'is_active' => true,
        ]);

        $this->standardRoomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Standard Double',
            'name_ja' => 'スタンダードダブル',
            'code' => 'STD-DBL',
            'base_price' => 10000.00,
            'capacity' => 2,
        ]);

        $this->room101 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->standardRoomType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'clean',
            'is_active' => true,
        ]);

        HotelPricingSetting::create([
            'hotel_id' => $this->hotel->id,
            'stacking_policy' => 'additive',
            'weekend_days' => [5, 6], // Friday & Saturday
            'weekend_increase_percentage' => 15.00,
            'rounding_policy' => 'whole_yen',
            'currency' => 'JPY',
            'tax_percentage' => 10.00,
            'tax_included' => true,
        ]);

        $this->pricingService = app(ReservationPricingService::class);
    }

    public function test_single_weekday_night_charges_exact_base_rate(): void
    {
        // 2026-06-02 (Tuesday) to 2026-06-03 (Wednesday) = 1 night
        $quote = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-06-02',
            '2026-06-03',
            $this->room101->id
        );

        $this->assertEquals(1, $quote['nights_count']);
        $this->assertEquals(10000.00, $quote['base_total']);
        $this->assertEquals(0.00, $quote['surcharge_total']);
        $this->assertEquals(10000.00, $quote['grand_total']);
        $this->assertTrue($quote['is_available']);
        $this->assertEquals('2026-06-02', $quote['nightly_breakdown'][0]['date']);
        $this->assertEquals(10000.00, $quote['nightly_breakdown'][0]['nightly_rate']);
    }

    public function test_checkout_date_is_never_charged_as_an_occupied_night(): void
    {
        // Stay from 2026-06-01 (Mon) to 2026-06-04 (Thu) = exactly 3 nights (Mon, Tue, Wed). Thu is checkout.
        $quote = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-06-01',
            '2026-06-04',
            $this->room101->id
        );

        $this->assertEquals(3, $quote['nights_count']);
        $this->assertEquals(30000.00, $quote['grand_total']);
        $dates = array_column($quote['nightly_breakdown'], 'date');
        $this->assertEquals(['2026-06-01', '2026-06-02', '2026-06-03'], $dates);
        $this->assertNotContains('2026-06-04', $dates);
    }

    public function test_weekend_pricing_adds_15_percent_on_friday_and_saturday(): void
    {
        // 2026-06-04 (Thu), 2026-06-05 (Fri), 2026-06-06 (Sat) -> checkout 2026-06-07 (Sun)
        // 3 nights: 1 weekday @ 10,000 + 2 weekend nights @ 11,500 = 33,000
        $quote = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-06-04',
            '2026-06-07',
            $this->room101->id
        );

        $this->assertEquals(3, $quote['nights_count']);
        $this->assertEquals(30000.00, $quote['base_total']);
        $this->assertEquals(3000.00, $quote['surcharge_total']); // 1,500 + 1,500
        $this->assertEquals(33000.00, $quote['grand_total']);

        // Check individual nights
        $this->assertEquals(10000.00, $quote['nightly_breakdown'][0]['nightly_rate']); // Thu
        $this->assertEquals(11500.00, $quote['nightly_breakdown'][1]['nightly_rate']); // Fri
        $this->assertEquals(11500.00, $quote['nightly_breakdown'][2]['nightly_rate']); // Sat
        $this->assertTrue($quote['nightly_breakdown'][1]['is_weekend']);
        $this->assertTrue($quote['nightly_breakdown'][2]['is_weekend']);
    }

    public function test_japanese_holiday_and_calendar_event_rate_surge(): void
    {
        // Seed Golden Week event: 2026-05-01 to 2026-05-06 (+35%)
        CalendarEvent::create([
            'hotel_id' => $this->hotel->id,
            'title' => 'Golden Week 2026',
            'title_ja' => 'ゴールデンウィーク2026',
            'event_type' => 'holiday',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-06',
            'percentage_adjustment' => 35.0,
            'demand_multiplier' => 1.35,
            'is_active' => true,
        ]);

        // 2026-05-01 is a Friday.
        // In Additive stacking: Base (10,000) * (1 + 15% weekend + 35% GW) = 10,000 * 1.50 = 15,000.
        $quote = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-05-01',
            '2026-05-02',
            $this->room101->id
        );

        $this->assertEquals(1, $quote['nights_count']);
        $this->assertEquals(15000.00, $quote['grand_total']);
        $this->assertEquals(5000.00, $quote['surcharge_total']);
        $rules = $quote['nightly_breakdown'][0]['applied_rules'];
        $this->assertCount(2, $rules); // Weekend + Golden Week
    }

    public function test_seasonal_pricing_rule_application(): void
    {
        PricingRule::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->standardRoomType->id,
            'rule_name' => 'Sakura Peak Season',
            'rule_type' => 'seasonal',
            'percentage_adjustment' => 20.0,
            'fixed_adjustment' => 500.0,
            'start_date' => '2026-03-25',
            'end_date' => '2026-04-10',
            'priority' => 10,
            'is_active' => true,
        ]);

        // 2026-04-01 is Wednesday (weekday). Base 10,000 + 20% (2,000) + 500 fixed = 12,500.
        $quote = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-04-01',
            '2026-04-02',
            $this->room101->id
        );

        $this->assertEquals(12500.00, $quote['grand_total']);
        $this->assertEquals(2500.00, $quote['surcharge_total']);
    }

    public function test_multiplicative_stacking_policy(): void
    {
        // Override with multiplicative policy
        // Base: 10,000, Weekend: 15%, Holiday: 20%
        // Multiplicative: 10,000 * 1.15 * 1.20 = 13,800
        CalendarEvent::create([
            'hotel_id' => $this->hotel->id,
            'title' => 'Culture Day',
            'title_ja' => '文化の日',
            'event_type' => 'holiday',
            'start_date' => '2026-11-06',
            'end_date' => '2026-11-06',
            'percentage_adjustment' => 20.0,
            'is_active' => true,
        ]);

        // 2026-11-06 is Friday
        $quote = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-11-06',
            '2026-11-07',
            $this->room101->id,
            null,
            ['stacking_policy' => 'multiplicative']
        );

        $this->assertEquals(13800.00, $quote['grand_total']);
        $this->assertEquals('multiplicative', $quote['stacking_policy']);
    }

    public function test_highest_only_stacking_policy(): void
    {
        // Base: 10,000, Weekend: 15%, Holiday: 25%
        // Highest only takes max(15%, 25%) = 25% -> 12,500
        CalendarEvent::create([
            'hotel_id' => $this->hotel->id,
            'title' => 'Festival Surge',
            'title_ja' => '夏祭りサージ',
            'event_type' => 'event',
            'start_date' => '2026-08-14',
            'end_date' => '2026-08-14',
            'percentage_adjustment' => 25.0,
            'is_active' => true,
        ]);

        // 2026-08-14 is Friday
        $quote = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-08-14',
            '2026-08-15',
            $this->room101->id,
            null,
            ['stacking_policy' => 'highest_only']
        );

        $this->assertEquals(12500.00, $quote['grand_total']);
        $this->assertEquals('highest_only', $quote['stacking_policy']);
    }

    public function test_rounding_policies(): void
    {
        // Base 10,333 with 15% weekend = 11,882.95
        $this->standardRoomType->update(['base_price' => 10333.00]);

        // 1. whole_yen -> 11883
        $qWhole = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-06-05', // Friday
            '2026-06-06',
            $this->room101->id,
            null,
            ['rounding_policy' => 'whole_yen']
        );
        $this->assertEquals(11883.00, $qWhole['grand_total']);

        // 2. nearest_100 -> 11900
        $qHundred = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-06-05',
            '2026-06-06',
            $this->room101->id,
            null,
            ['rounding_policy' => 'nearest_100']
        );
        $this->assertEquals(11900.00, $qHundred['grand_total']);

        // 3. none -> 11882.95
        $qNone = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-06-05',
            '2026-06-06',
            $this->room101->id,
            null,
            ['rounding_policy' => 'none']
        );
        $this->assertEquals(11882.95, $qNone['grand_total']);
    }

    public function test_room_availability_detection_in_quote(): void
    {
        $guest = \App\Models\Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'email' => 'taro@example.com',
        ]);

        // Existing reservation in room 101 for 2026-07-10 to 2026-07-15
        Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $guest->id,
            'room_id' => $this->room101->id,
            'check_in_date' => '2026-07-10',
            'check_out_date' => '2026-07-15',
            'adults' => 2,
            'total_price' => 50000.00,
            'status' => 'confirmed',
        ]);

        // Conflicting quote (2026-07-12 to 2026-07-14)
        $quote = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-07-12',
            '2026-07-14',
            $this->room101->id
        );

        $this->assertFalse($quote['is_available']);
        $this->assertNotNull($quote['conflicting_code']);

        // Non-conflicting quote (2026-07-15 to 2026-07-18)
        $freeQuote = $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-07-15',
            '2026-07-18',
            $this->room101->id
        );
        $this->assertTrue($freeQuote['is_available']);
    }

    public function test_invalid_date_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->pricingService->calculateQuote(
            $this->hotel->id,
            $this->standardRoomType->id,
            '2026-06-10',
            '2026-06-10', // Same day checkout not allowed
            $this->room101->id
        );
    }
}
