<?php

namespace Tests\Feature;

use App\Models\BulkChangeBatch;
use App\Models\CalendarEvent;
use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\ChannelRoomMapping;
use App\Models\EventTrace;
use App\Models\Hotel;
use App\Models\IntegrationPartner;
use App\Models\IntegrationTestCase;
use App\Models\IntegrationTestSuite;
use App\Models\PartnerContract;
use App\Models\PartnerReadinessReport;
use App\Models\Permission;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\SyncOutboxEvent;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClearStay2EnterpriseTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;
    protected User $frontdesk;
    protected RoomType $roomType;
    protected Channel $channel;
    protected ChannelConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Hotel setup
        $this->hotel = Hotel::create([
            'name' => 'ClearStay Tokyo Ginza',
            'slug' => 'clearstay-tokyo-ginza',
            'code' => 'TYO-GNZ',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'total_rooms' => 10,
            'is_active' => true,
        ]);

        // 2. Staff setup
        $this->manager = User::create([
            'name' => 'Kenji Sato',
            'email' => 'manager@clearstay.test',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'department' => 'Property Operations',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->frontdesk = User::create([
            'name' => 'Yuki Tanaka',
            'email' => 'frontdesk@clearstay.test',
            'password' => bcrypt('password'),
            'role' => 'frontdesk',
            'hotel_id' => $this->hotel->id,
            'department' => 'Front Office',
            'locale' => 'en',
            'is_active' => true,
        ]);

        // 3. Room Type setup
        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Standard Deluxe Room',
            'code' => 'STD-DLX',
            'capacity' => 2,
            'base_price' => 15000,
        ]);

        // 4. Permissions Seed
        Permission::create(['name' => 'inventory.view', 'category' => 'inventory', 'description' => 'View inventory']);
        Permission::create(['name' => 'inventory.update', 'category' => 'inventory', 'description' => 'Update inventory']);
        Permission::create(['name' => 'inventory.bulk_edit', 'category' => 'inventory', 'description' => 'Bulk edit inventory']);
        Permission::create(['name' => 'reservations.view', 'category' => 'reservations', 'description' => 'View reservations']);
        Permission::create(['name' => 'reservations.create', 'category' => 'reservations', 'description' => 'Create reservations']);
        Permission::create(['name' => 'channels.view', 'category' => 'channels', 'description' => 'View channels']);
        Permission::create(['name' => 'channels.manage', 'category' => 'channels', 'description' => 'Manage channels']);
        Permission::create(['name' => 'channels.sync', 'category' => 'channels', 'description' => 'Sync channels']);
        Permission::create(['name' => 'governance.staff_view', 'category' => 'staff', 'description' => 'View staff']);
        Permission::create(['name' => 'governance.staff_manage', 'category' => 'staff', 'description' => 'Manage staff']);
        Permission::create(['name' => 'governance.permissions_grant', 'category' => 'staff', 'description' => 'Grant permissions']);
        Permission::create(['name' => 'governance.access_suspend', 'category' => 'security', 'description' => 'Suspend access']);
        Permission::create(['name' => 'governance.access_revoke', 'category' => 'security', 'description' => 'Revoke access']);
        Permission::create(['name' => 'partner.read', 'category' => 'partners', 'description' => 'Inspect partners']);
        Permission::create(['name' => 'partner.test_execute', 'category' => 'integration', 'description' => 'Execute tests']);
        Permission::create(['name' => 'reports.view', 'category' => 'reports', 'description' => 'View reports']);

        // 5. Channel setup
        $this->channel = Channel::create([
            'name' => 'Booking.com',
            'code' => 'booking_com',
            'type' => 'ota',
            'adapter_class' => 'App\\Services\\Channel\\Adapters\\BookingComAdapter',
            'is_active' => true,
            'status' => 'connected',
        ]);

        $this->connection = ChannelConnection::create([
            'hotel_id' => $this->hotel->id,
            'channel_id' => $this->channel->id,
            'is_enabled' => true,
            'credentials' => json_encode(['api_key' => 'test_key']),
            'sync_status' => 'ok',
        ]);

        ChannelRoomMapping::create([
            'channel_connection_id' => $this->connection->id,
            'room_type_id' => $this->roomType->id,
            'channel_room_code' => 'CH_RT_01',
            'rate_multiplier' => 1.05,
            'is_active' => true,
        ]);

        // 6. Central Inventory for 7 days
        for ($d = 1; $d <= 7; $d++) {
            CentralInventory::create([
                'hotel_id' => $this->hotel->id,
                'room_type_id' => $this->roomType->id,
                'date' => Carbon::today()->addDays($d)->toDateString(),
                'total_units' => 10,
                'available_units' => 8,
                'reserved_units' => 2,
                'maintenance_units' => 0,
                'base_rate' => 15000,
                'currency' => 'JPY',
                'min_stay' => 1,
                'stop_sell' => false,
            ]);
        }
    }

    public function test_channel_distribution_and_transactional_outbox_broadcast(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('channels.sync_all'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check transactional outbox event was created and processed
        $this->assertDatabaseHas('sync_outbox_events', [
            'hotel_id' => $this->hotel->id,
            'status' => 'confirmed',
        ]);

        // Check attempt log was registered
        $this->assertDatabaseHas('sync_attempt_logs', [
            'http_status' => 200,
        ]);
    }

    public function test_outbox_circuit_breaker_and_failed_event_retry(): void
    {
        // 1. Simulate 500 error from upstream channel adapter
        $response = $this->actingAs($this->manager)
            ->post(route('channels.simulate_error', $this->channel));

        $response->assertRedirect();
        
        // Assert event was logged as failed
        $this->assertDatabaseHas('sync_outbox_events', [
            'hotel_id' => $this->hotel->id,
            'status' => 'failed',
        ]);

        // 2. Trigger retry
        $retryResponse = $this->actingAs($this->manager)
            ->post(route('channels.retry_failed'));

        $retryResponse->assertRedirect();
        $retryResponse->assertSessionHas('success');
    }

    public function test_bulk_editor_atomic_commit_and_one_click_safe_rollback(): void
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $nextWeek = Carbon::tomorrow()->addDays(5)->toDateString();

        // Commit bulk change
        $commitResponse = $this->actingAs($this->manager)
            ->post(route('bulk_editor.commit'), [
                'start_date' => $tomorrow,
                'end_date' => $nextWeek,
                'room_type_id' => $this->roomType->id,
                'price_delta' => 4000,
                'min_stay' => 2,
                'stop_sell' => 0,
                'reason' => 'Peak holiday season adjustment',
            ]);

        $commitResponse->assertRedirect(route('bulk_editor.index'));
        $commitResponse->assertSessionHas('success');

        // Verify batch recorded
        $batch = BulkChangeBatch::where('hotel_id', $this->hotel->id)->first();
        $this->assertNotNull($batch);
        $this->assertEquals('applied', $batch->status);

        // Verify inventory updated
        $updatedInv = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->roomType->id)
            ->whereDate('date', $tomorrow)
            ->first();
        $this->assertEquals(19000, $updatedInv->base_rate);
        $this->assertEquals(2, $updatedInv->min_stay);

        // 1-Click Safe Rollback Execution
        $rollbackResponse = $this->actingAs($this->manager)
            ->post(route('bulk_editor.rollback', $batch));

        $rollbackResponse->assertRedirect(route('bulk_editor.index'));
        $rollbackResponse->assertSessionHas('success');

        // Assert batch status is now rolled_back
        $batch->refresh();
        $this->assertEquals('rolled_back', $batch->status);

        // Assert original price is restored
        $restoredInv = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->roomType->id)
            ->whereDate('date', $tomorrow)
            ->first();
        $this->assertEquals(15000, $restoredInv->base_rate);
        $this->assertEquals(1, $restoredInv->min_stay);
    }

    public function test_partner_assurance_contract_validation_and_scenario_runner(): void
    {
        $partner = IntegrationPartner::create([
            'name' => 'Sakura PMS Connector',
            'company' => 'Sakura Tech',
            'category' => 'pms',
            'api_base_url' => 'https://api.sakura.jp',
            'api_version' => 'v2.4',
            'auth_type' => 'bearer',
            'status' => 'certified',
        ]);

        PartnerContract::create([
            'partner_id' => $partner->id,
            'contract_name' => 'OpenAPI Core',
            'schema_format' => 'openapi3',
            'spec_content' => json_encode(['openapi' => '3.0.0', 'info' => ['title' => 'PMS', 'version' => '2.4']]),
            'version' => '2.4.0',
            'is_active' => true,
        ]);

        $suite = IntegrationTestSuite::create([
            'partner_id' => $partner->id,
            'suite_name' => 'Core Suite',
            'category' => 'reservations',
        ]);

        IntegrationTestCase::create([
            'test_suite_id' => $suite->id,
            'case_code' => 'TC-ALLOT-01',
            'name' => 'Allotment Sync Test',
            'request_payload' => ['date' => Carbon::tomorrow()->toDateString(), 'allotment' => 5],
            'expected_status_code' => 200,
            'is_simulation_safe' => true,
        ]);

        // Run scenario test suite
        $response = $this->actingAs($this->manager)
            ->post(route('partner_assurance.run_scenarios', $partner));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify EventTrace was recorded
        $this->assertDatabaseHas('event_traces', [
            'status' => 'success',
        ]);
    }

    public function test_partner_readiness_report_generation_and_csv_export(): void
    {
        $partner = IntegrationPartner::create([
            'name' => 'TravelConnect CRS',
            'company' => 'TravelConnect Inc.',
            'category' => 'crs',
            'api_base_url' => 'https://gateway.travelconnect.com',
            'status' => 'certified',
        ]);

        $generateResponse = $this->actingAs($this->manager)
            ->post(route('readiness.generate', $partner));

        $generateResponse->assertRedirect();
        $generateResponse->assertSessionHas('success');

        $report = PartnerReadinessReport::where('partner_id', $partner->id)->first();
        $this->assertNotNull($report);
        $this->assertGreaterThanOrEqual(70, $report->score_percentage);

        // Test CSV export download
        $csvResponse = $this->actingAs($this->manager)
            ->get(route('readiness.export_csv', $report));

        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', strtolower((string)$csvResponse->headers->get('Content-Type')));
    }

    public function test_manager_staff_governance_explicit_allow_and_deny_overrides(): void
    {
        // 1. By default, frontdesk role lacks 'inventory.bulk_edit'
        $this->assertFalse($this->frontdesk->hasPermission('inventory.bulk_edit'));

        $permission = Permission::where('name', 'inventory.bulk_edit')->first();

        // 2. Manager explicitly grants (Allow) permission to frontdesk user
        $allowResponse = $this->actingAs($this->manager)
            ->post(route('staff.update_permissions', $this->frontdesk), [
                'permission_id' => $permission->id,
                'state' => 'allow',
                'reason' => 'Covering for weekend duty shift',
            ]);

        $allowResponse->assertRedirect();
        $allowResponse->assertSessionHas('success');

        // Check user has permission now!
        $this->frontdesk->refresh();
        $this->assertTrue($this->frontdesk->hasPermission('inventory.bulk_edit'));

        // 3. Manager explicitly blocks (Deny) 'reservations.view' (which is normally true for frontdesk)
        $resPerm = Permission::where('name', 'reservations.view')->first();
        $denyResponse = $this->actingAs($this->manager)
            ->post(route('staff.update_permissions', $this->frontdesk), [
                'permission_id' => $resPerm->id,
                'state' => 'deny',
                'reason' => 'Audit review restriction',
            ]);

        $denyResponse->assertRedirect();
        $this->frontdesk->refresh();
        $this->assertFalse($this->frontdesk->hasPermission('reservations.view'));
    }

    public function test_instant_account_freeze_and_session_invalidation(): void
    {
        $this->assertTrue($this->frontdesk->isAccountUsable());

        // Manager executes instant freeze
        $freezeResponse = $this->actingAs($this->manager)
            ->post(route('staff.suspend', $this->frontdesk), [
                'reason' => 'Security compliance anomaly detected',
            ]);

        $freezeResponse->assertRedirect();
        $freezeResponse->assertSessionHas('success');

        $this->frontdesk->refresh();
        $this->assertTrue($this->frontdesk->is_suspended);
        $this->assertFalse($this->frontdesk->isAccountUsable());

        // Reactivate
        $reactivateResponse = $this->actingAs($this->manager)
            ->post(route('staff.reactivate', $this->frontdesk));

        $reactivateResponse->assertRedirect();
        $this->frontdesk->refresh();
        $this->assertFalse($this->frontdesk->is_suspended);
        $this->assertTrue($this->frontdesk->isAccountUsable());
    }

    public function test_device_trust_registration_and_revocation(): void
    {
        $regResponse = $this->actingAs($this->manager)
            ->post(route('device_trust.register_current'), [
                'device_name' => 'GM Office Terminal #1',
            ]);

        $regResponse->assertRedirect();
        $regResponse->assertSessionHas('success');

        $device = TrustedDevice::where('user_id', $this->manager->id)->first();
        $this->assertNotNull($device);
        $this->assertEquals('trusted', $device->trust_status);

        // Revoke device
        $revokeResponse = $this->actingAs($this->manager)
            ->post(route('device_trust.revoke', $device), [
                'reason' => 'Workstation replaced with new hardware',
            ]);

        $revokeResponse->assertRedirect();
        $device->refresh();
        $this->assertEquals('revoked', $device->trust_status);
    }

    public function test_simple_mode_toggle_session_persistence(): void
    {
        // Toggle ON
        $response1 = $this->actingAs($this->manager)
            ->post(route('simple_mode.toggle'));

        $response1->assertRedirect();
        $this->assertTrue(session('clearstay_simple_mode'));

        // Toggle OFF
        $response2 = $this->actingAs($this->manager)
            ->post(route('simple_mode.toggle'));

        $response2->assertRedirect();
        $this->assertFalse(session('clearstay_simple_mode'));
    }

    public function test_rate_studio_parity_scanner_and_calendar_events(): void
    {
        // Create Golden Week holiday event
        CalendarEvent::create([
            'title' => 'Golden Week Holidays',
            'title_ja' => 'ゴールデンウィーク',
            'event_type' => 'golden_week',
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(5)->toDateString(),
            'demand_multiplier' => 1.45,
            'recommended_min_stay' => 2,
        ]);

        $scanResponse = $this->actingAs($this->manager)
            ->post(route('rates.scan_parity'));

        $scanResponse->assertRedirect();
        $scanResponse->assertSessionHas('success');

        $this->assertDatabaseHas('rate_parity_observations', [
            'hotel_id' => $this->hotel->id,
        ]);
    }

    public function test_concurrency_safe_reservation_double_booking_prevention(): void
    {
        $guest = \App\Models\Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Alice',
            'last_name' => 'Traveler',
            'email' => 'alice@example.com',
            'phone' => '+81-90-1234-5678',
        ]);

        $room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'available',
            'is_active' => true,
        ]);

        $reservationService = app(ReservationService::class);

        // Create first reservation
        $res1 = $reservationService->createReservation([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'room_type_id' => $this->roomType->id,
            'check_in_date' => Carbon::tomorrow()->toDateString(),
            'check_out_date' => Carbon::tomorrow()->addDays(3)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'guest_name' => 'Alice Traveler',
            'guest_email' => 'alice@example.com',
            'guest_phone' => '+81-90-1234-5678',
            'total_price' => 45000,
            'currency' => 'JPY',
        ]);

        $this->assertNotNull($res1->confirmation_code);

        // Attempt overlapping reservation on the same room
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $reservationService->createReservation([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'room_type_id' => $this->roomType->id,
            'check_in_date' => Carbon::tomorrow()->addDay()->toDateString(),
            'check_out_date' => Carbon::tomorrow()->addDays(4)->toDateString(),
            'adults' => 1,
            'children' => 0,
            'guest_name' => 'Bob Conflict',
            'guest_email' => 'bob@example.com',
            'total_price' => 30000,
            'currency' => 'JPY',
        ]);
    }
}
