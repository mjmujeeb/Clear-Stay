<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\HotelPricingSetting;
use App\Models\PricingAuditLog;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReceptionistReservationPricingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $receptionist;
    protected User $manager;
    protected RoomType $roomType;
    protected Room $room201;
    protected Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Kyoto Central',
            'slug' => 'clearstay-kyoto-central',
            'code' => 'CS-KYO',
            'address' => 'Karasuma-dori',
            'city' => 'Kyoto',
            'country' => 'JP',
            'total_rooms' => 10,
            'is_active' => true,
        ]);

        $this->receptionist = User::create([
            'name' => 'Receptionist Hana',
            'email' => 'hana@clearstay.test',
            'password' => Hash::make('password'),
            'role' => 'frontdesk',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->manager = User::create([
            'name' => 'General Manager Ken',
            'email' => 'ken.manager@clearstay.test',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Superior Tatami Suite',
            'name_ja' => 'スーペリア和室スイート',
            'code' => 'SUP-TTM',
            'base_price' => 20000.00,
            'capacity' => 4,
        ]);

        $this->room201 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '201',
            'floor' => 2,
            'status' => 'clean',
            'is_active' => true,
        ]);

        $this->guest = Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Ichiro',
            'last_name' => 'Suzuki',
            'email' => 'ichiro@example.jp',
            'phone' => '+81-90-1111-2222',
        ]);

        HotelPricingSetting::create([
            'hotel_id' => $this->hotel->id,
            'stacking_policy' => 'additive',
            'weekend_days' => [5, 6], // Fri, Sat
            'weekend_increase_percentage' => 15.00,
            'rounding_policy' => 'whole_yen',
            'currency' => 'JPY',
            'tax_percentage' => 10.00,
            'tax_included' => true,
        ]);
    }

    public function test_receptionist_can_fetch_live_price_quote_via_ajax(): void
    {
        $this->actingAs($this->receptionist);

        // Fetch quote for 3 nights from next Tuesday
        $tuesday = Carbon::now()->next(Carbon::TUESDAY)->toDateString();
        $friday = Carbon::now()->next(Carbon::TUESDAY)->addDays(3)->toDateString();

        $response = $this->postJson(route('reservations.price_quote'), [
            'room_id' => $this->room201->id,
            'check_in_date' => $tuesday,
            'check_out_date' => $friday,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('quote.nights_count', 3);
        $response->assertJsonPath('quote.base_total', 60000);
        $response->assertJsonPath('quote.is_available', true);
        $this->assertCount(3, $response->json('quote.nightly_breakdown'));
    }

    public function test_receptionist_creates_reservation_with_authoritative_server_price_and_snapshot(): void
    {
        $this->actingAs($this->receptionist);

        $nextFriday = Carbon::now()->next(Carbon::FRIDAY)->toDateString();
        $nextSunday = Carbon::now()->next(Carbon::FRIDAY)->addDays(2)->toDateString();

        // Client attempts to submit with a forged or lower total_price (e.g. 100 JPY)
        $response = $this->post(route('reservations.store'), [
            'guest_mode' => 'existing',
            'guest_id' => $this->guest->id,
            'room_id' => $this->room201->id,
            'check_in_date' => $nextFriday,
            'check_out_date' => $nextSunday,
            'adults' => 2,
            'children' => 0,
            'total_price' => 100.00, // Malicious forged price
        ]);

        $response->assertRedirect(route('reservations.index'));

        $reservation = Reservation::where('room_id', $this->room201->id)->first();
        $this->assertNotNull($reservation);
        // Assert the server authoritatively ignored the forged 100 JPY and charged 46,000 JPY (Fri + Sat @ 23k)
        $this->assertEquals(46000.00, $reservation->total_price);

        // Assert immutable snapshot was created
        $snapshot = $reservation->pricingSnapshot;
        $this->assertNotNull($snapshot);
        $this->assertEquals(46000.00, $snapshot->grand_total);
        $this->assertEquals(40000.00, $snapshot->base_total);
        $this->assertEquals(6000.00, $snapshot->surcharge_total);
        $this->assertCount(2, $snapshot->nightly_breakdown);
    }

    public function test_changing_reservation_dates_recalculates_price_and_updates_snapshot(): void
    {
        $this->actingAs($this->receptionist);

        $tuesday = Carbon::now()->next(Carbon::TUESDAY)->toDateString();
        $wednesday = Carbon::now()->next(Carbon::TUESDAY)->addDay()->toDateString();
        $nextFriday = Carbon::now()->next(Carbon::FRIDAY)->toDateString();
        $nextSunday = Carbon::now()->next(Carbon::FRIDAY)->addDays(2)->toDateString();

        // Initial 1 night on weekday
        $reservation = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room201->id,
            'check_in_date' => $tuesday,
            'check_out_date' => $wednesday,
            'adults' => 2,
            'total_price' => 20000.00,
            'status' => 'confirmed',
        ]);

        // Change dates to weekend stay: Friday to Sunday (2 nights @ 23,000 = 46,000)
        $response = $this->postJson(route('reservations.change_dates', $reservation->id), [
            'check_in_date' => $nextFriday,
            'check_out_date' => $nextSunday,
            'room_id' => $this->room201->id,
            'reason' => 'Guest shifted to weekend getaway',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $reservation->refresh();
        $this->assertEquals($nextFriday, $reservation->check_in_date->toDateString());
        $this->assertEquals($nextSunday, $reservation->check_out_date->toDateString());
        $this->assertEquals(46000.00, $reservation->total_price);

        // Verify snapshot updated
        $snapshot = $reservation->pricingSnapshot;
        $this->assertNotNull($snapshot);
        $this->assertEquals(46000.00, $snapshot->grand_total);
    }

    public function test_double_booking_is_prevented_under_concurrency_checks(): void
    {
        $this->actingAs($this->receptionist);

        $day1 = Carbon::now()->addDays(10)->toDateString();
        $day5 = Carbon::now()->addDays(14)->toDateString();
        $day3 = Carbon::now()->addDays(12)->toDateString();
        $day6 = Carbon::now()->addDays(15)->toDateString();

        // Create initial confirmed reservation
        Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room201->id,
            'check_in_date' => $day1,
            'check_out_date' => $day5,
            'adults' => 2,
            'total_price' => 80000.00,
            'status' => 'confirmed',
        ]);

        // Second booking attempt overlapping the same room
        $response = $this->post(route('reservations.store'), [
            'guest_mode' => 'existing',
            'guest_id' => $this->guest->id,
            'room_id' => $this->room201->id,
            'check_in_date' => $day3,
            'check_out_date' => $day6,
            'adults' => 1,
        ]);

        $response->assertSessionHasErrors(['room_id']);
        // Verify only 1 reservation exists
        $this->assertEquals(1, Reservation::where('room_id', $this->room201->id)->count());
    }

    public function test_manager_can_access_pricing_settings_page(): void
    {
        $this->actingAs($this->manager);

        $response = $this->get(route('pricing_settings.index'));
        $response->assertOk();
        $response->assertSee('Superior Tatami Suite');
        $response->assertSee('ADDITIVE');
    }

    public function test_manager_can_update_base_price_with_audit_trail(): void
    {
        $this->actingAs($this->manager);

        $response = $this->post(route('pricing_settings.update_base_price'), [
            'room_type_id' => $this->roomType->id,
            'base_price' => 24000.00,
            'reason' => 'Annual inflation adjustment',
        ]);

        $response->assertRedirect(route('pricing_settings.index'));

        $this->roomType->refresh();
        $this->assertEquals(24000.00, $this->roomType->base_price);

        // Verify audit log
        $log = PricingAuditLog::where('hotel_id', $this->hotel->id)
            ->where('action', 'update_base_price')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->manager->id, $log->user_id);
        $this->assertEquals(24000.00, $log->new_values['base_price']);
    }

    public function test_manager_can_update_weekend_rules_with_audit_trail(): void
    {
        $this->actingAs($this->manager);

        $response = $this->post(route('pricing_settings.update_weekend_rules'), [
            'weekend_days' => [5, 6, 0], // Friday, Saturday, Sunday
            'weekend_increase_percentage' => 20.00,
            'reason' => 'Include Sunday night in weekend rate',
        ]);

        $response->assertRedirect(route('pricing_settings.index'));

        $settings = HotelPricingSetting::where('hotel_id', $this->hotel->id)->first();
        $this->assertEquals([5, 6, 0], $settings->weekend_days);
        $this->assertEquals(20.00, $settings->weekend_increase_percentage);

        $log = PricingAuditLog::where('hotel_id', $this->hotel->id)
            ->where('action', 'update_weekend_rate')
            ->first();
        $this->assertNotNull($log);
    }

    public function test_manager_can_create_and_delete_seasonal_pricing_rule(): void
    {
        $this->actingAs($this->manager);

        $startDate = Carbon::now()->addDays(20)->toDateString();
        $endDate = Carbon::now()->addDays(40)->toDateString();

        // Create rule
        $response = $this->post(route('pricing_settings.store_seasonal_rule'), [
            'rule_name' => 'Autumn Foliage Festival',
            'rule_type' => 'seasonal',
            'percentage_adjustment' => 25.0,
            'fixed_adjustment' => 1000.0,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'priority' => 10,
        ]);

        $response->assertRedirect(route('pricing_settings.index'));

        $rule = PricingRule::where('hotel_id', $this->hotel->id)
            ->where('rule_name', 'Autumn Foliage Festival')
            ->first();
        $this->assertNotNull($rule);
        $this->assertEquals(25.0, $rule->percentage_adjustment);

        // Delete rule
        $delResponse = $this->delete(route('pricing_settings.delete_seasonal_rule', $rule->id));
        $delResponse->assertRedirect(route('pricing_settings.index'));
        $this->assertNull(PricingRule::find($rule->id));
    }

    public function test_manager_can_update_stacking_policy(): void
    {
        $this->actingAs($this->manager);

        $response = $this->post(route('pricing_settings.update_policy'), [
            'stacking_policy' => 'multiplicative',
            'rounding_policy' => 'nearest_100',
            'tax_percentage' => 10.0,
            'tax_included' => '1',
            'reason' => 'Switch to multiplicative for high-season yield management',
        ]);

        $response->assertRedirect(route('pricing_settings.index'));

        $settings = HotelPricingSetting::where('hotel_id', $this->hotel->id)->first();
        $this->assertEquals('multiplicative', $settings->stacking_policy);
        $this->assertEquals('nearest_100', $settings->rounding_policy);
    }

    public function test_pricing_simulation_api(): void
    {
        $this->actingAs($this->manager);

        $simIn = Carbon::now()->addDays(3)->toDateString();
        $simOut = Carbon::now()->addDays(6)->toDateString();

        $response = $this->postJson(route('pricing_settings.simulate'), [
            'room_type_id' => $this->roomType->id,
            'check_in_date' => $simIn,
            'check_out_date' => $simOut,
            'override_stacking_policy' => 'highest_only',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('quote.nights_count', 3);
        $response->assertJsonPath('quote.stacking_policy', 'highest_only');
    }
}
