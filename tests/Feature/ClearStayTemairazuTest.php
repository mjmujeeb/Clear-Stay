<?php

namespace Tests\Feature;

use App\Models\BookingSnapshot;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\InventoryReconciliation;
use App\Models\PaymentReconciliation;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Analytics\BookingAnalyticsService;
use App\Services\Payment\PaymentReconciliationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearStayTemairazuTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;
    protected Guest $guest;
    protected RoomType $roomType;
    protected Room $room;
    protected Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Kyoto Ryokan',
            'slug' => 'clearstay-kyoto-ryokan',
            'code' => 'KYO-RYO',
            'address' => 'Gion, Higashiyama-ku',
            'city' => 'Kyoto',
            'country' => 'JP',
            'total_rooms' => 15,
            'is_active' => true,
        ]);

        $this->guest = Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Taro',
            'last_name' => 'Tanaka',
            'email' => 'tanaka@example.com',
            'phone' => '090-1234-5678',
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Traditional Japanese Tatami Suite',
            'code' => 'TATAMI-STE',
            'base_price' => 32000,
            'max_occupancy' => 4,
            'total_rooms' => 10,
        ]);

        $this->room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '101',
            'status' => 'clean',
        ]);

        $this->manager = User::create([
            'name' => 'Takeshi Yamada',
            'email' => 'yamada.manager@clearstay.test',
            'password' => bcrypt('Password123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'department' => 'Operations',
            'locale' => 'ja',
            'is_active' => true,
        ]);

        $this->channel = Channel::create([
            'name' => 'Rakuten Travel',
            'code' => 'rakuten',
            'type' => 'ota',
            'adapter_class' => 'App\\Services\\Channel\\Adapters\\RakutenAdapter',
            'is_active' => true,
            'capabilities' => ['rates', 'inventory', 'reservations'],
        ]);

        ChannelConnection::create([
            'hotel_id' => $this->hotel->id,
            'channel_id' => $this->channel->id,
            'is_enabled' => true,
            'credentials' => ['service_secret' => 'rakuten-sec-test'],
            'sync_interval_mins' => 15,
            'sync_status' => 'connected',
        ]);
    }

    /**
     * Test 1: Advanced Booking Analytics (BA+) Reservation Status KPIs Calculation
     */
    public function test_booking_analytics_status_kpis_calculation(): void
    {
        // Create 2nd room
        $room1 = $this->room;
        $room2 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '102',
            'status' => 'clean',
        ]);

        // Create confirmed reservation
        Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'confirmation_code' => 'RES-TEST-BA1',
            'room_id' => $room1->id,
            'room_type_id' => $this->roomType->id,
            'channel_id' => $this->channel->id,
            'check_in_date' => Carbon::today()->toDateString(),
            'check_out_date' => Carbon::today()->addDays(2)->toDateString(),
            'nights' => 2,
            'adults' => 2,
            'children' => 0,
            'guest_name' => 'Taro Tanaka',
            'guest_email' => 'taro@example.com',
            'total_price' => 64000.00,
            'currency' => 'JPY',
            'status' => 'confirmed',
        ]);

        $analyticsService = app(BookingAnalyticsService::class);
        $metrics = $analyticsService->getReservationStatusMetrics(
            $this->hotel->id,
            Carbon::today()->toDateString(),
            Carbon::today()->addDays(1)->toDateString()
        );

        $this->assertEquals(2, $metrics['total_rooms']);
        $this->assertEquals(1, $metrics['rooms_sold']);
        $this->assertEquals(64000.00, $metrics['room_revenue']);
        $this->assertEquals(64000.00, $metrics['adr']); // 64000 / 1
        $this->assertGreaterThan(0, $metrics['occupancy_rate']);

        // Verify page loads cleanly
        $response = $this->actingAs($this->manager)->get(route('booking_analytics.index'));
        $response->assertOk();
        $response->assertViewHas('metrics');
        $response->assertViewHas('historicalComparison');
        $response->assertViewHas('bookingPace');
    }

    /**
     * Test 2: Historical Snapshot Capture and Comparison Across Horizons
     */
    public function test_booking_snapshot_capture_and_historical_comparison(): void
    {
        $targetDate = Carbon::today()->addDays(5);
        $analyticsService = app(BookingAnalyticsService::class);

        // Capture snapshot
        $snapshot = $analyticsService->captureSnapshot($this->hotel, $targetDate);
        $this->assertNotNull($snapshot);
        $this->assertEquals($targetDate->toDateString(), $snapshot->stay_date->toDateString());

        // Test manual capture route
        $captureResponse = $this->actingAs($this->manager)->post(route('booking_analytics.capture'), [
            'stay_date' => $targetDate->toDateString(),
        ]);
        $captureResponse->assertRedirect();
        $captureResponse->assertSessionHas('success');

        // Verify comparison
        $comparison = $analyticsService->getHistoricalComparison($this->hotel->id, $targetDate->toDateString());
        $this->assertNotNull($comparison['current']);
        $this->assertEquals($targetDate->toDateString(), $comparison['target_stay_date']);
    }

    /**
     * Test 3: Booking CSV Export Download
     */
    public function test_booking_analytics_csv_export(): void
    {
        $response = $this->actingAs($this->manager)->get(route('booking_analytics.export_csv'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', strtolower((string)$response->headers->get('Content-Type')));
    }

    /**
     * Test 4: Reservation-to-Payment Reconciliation Batch & Discrepancy Matching
     */
    public function test_payment_reconciliation_batch_execution_and_resolution(): void
    {
        // Create sample reservation
        $res = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'confirmation_code' => 'RES-PAY-RECON-1',
            'room_id' => $this->room->id,
            'room_type_id' => $this->roomType->id,
            'channel_id' => $this->channel->id,
            'check_in_date' => Carbon::today()->toDateString(),
            'check_out_date' => Carbon::today()->addDays(1)->toDateString(),
            'nights' => 1,
            'adults' => 1,
            'children' => 0,
            'guest_name' => 'Kenzo Tange',
            'guest_email' => 'kenzo@example.com',
            'total_price' => 32000.00,
            'currency' => 'JPY',
            'status' => 'confirmed',
        ]);

        $paymentService = app(PaymentReconciliationService::class);
        $result = $paymentService->runReconciliationBatch($this->hotel->id);

        $this->assertGreaterThan(0, $result['total_evaluated']);

        $recon = PaymentReconciliation::where('reservation_id', $res->id)->first();
        $this->assertNotNull($recon);
        $this->assertEquals(32000.00, $recon->expected_amount);

        // Test page render
        $pageResponse = $this->actingAs($this->manager)->get(route('payment_reconciliation.index'));
        $pageResponse->assertOk();
        $pageResponse->assertViewHas('reconciliations');
        $pageResponse->assertSee($recon->reservation_reference);

        // Test manager resolving discrepancy
        $resolveResponse = $this->actingAs($this->manager)->post(route('payment_reconciliation.resolve', $recon), [
            'action' => 'force_match',
            'notes' => 'Verified virtual card settlement with bank audit log.',
        ]);
        $resolveResponse->assertRedirect();
        $resolveResponse->assertSessionHas('success');

        $recon->refresh();
        $this->assertEquals('matched', $recon->reconciliation_status);
        $this->assertEquals('settled', $recon->settlement_status);

        // Test CSV download
        $csvResponse = $this->actingAs($this->manager)->get(route('payment_reconciliation.export_csv'));
        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', strtolower((string)$csvResponse->headers->get('Content-Type')));
    }

    /**
     * Test 5: Central Inventory Reconciliation & Real-Time Discrepancy Resolution
     */
    public function test_inventory_reconciliation_audit_and_resolution(): void
    {
        $reconPage = $this->actingAs($this->manager)->get(route('inventory_reconciliation.index'));
        $reconPage->assertOk();
        $reconPage->assertViewHas('reconciliations');
        $reconPage->assertViewHas('totalAudited');

        // Locate existing record and mark as discrepancy
        $discrepancy = InventoryReconciliation::where('hotel_id', $this->hotel->id)->first();
        $discrepancy->status = 'discrepancy';
        $discrepancy->difference = 2;
        $discrepancy->channel_units = $discrepancy->central_units - 2;
        $discrepancy->suggested_action = 'Re-align central inventory';
        $discrepancy->save();

        $resolveResponse = $this->actingAs($this->manager)->post(route('inventory_reconciliation.resolve', $discrepancy));
        $resolveResponse->assertRedirect();
        $resolveResponse->assertSessionHas('success');

        $discrepancy->refresh();
        $this->assertEquals('resolved', $discrepancy->status);
        $this->assertEquals(0, $discrepancy->difference);
    }
}
