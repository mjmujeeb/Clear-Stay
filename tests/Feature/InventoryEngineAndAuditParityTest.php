<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BulkChangeBatch;
use App\Models\CentralInventory;
use App\Models\Channel;
use App\Models\ChannelConnection;
use App\Models\Hotel;
use App\Models\InventoryAuditRun;
use App\Models\InventoryReconciliation;
use App\Models\RoomType;
use App\Models\SyncOutboxEvent;
use App\Models\User;
use App\Services\Inventory\CentralInventoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryEngineAndAuditParityTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected RoomType $roomType;
    protected Channel $bookingCom;
    protected Channel $agoda;
    protected User $manager;
    protected User $receptionist;
    protected CentralInventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Ginza Flagship',
            'slug' => 'clearstay-ginza-flagship',
            'code' => 'CS-GINZA',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'Japan',
            'total_rooms' => 30,
            'is_active' => true,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Executive River Suite',
            'code' => 'EXE-RIV',
            'base_price' => 32000.00,
            'max_occupancy' => 2,
            'total_rooms' => 10,
        ]);

        $this->bookingCom = Channel::create([
            'name' => 'Booking.com Enterprise',
            'code' => 'BOOKING_COM',
            'commission_rate' => 15.00,
            'is_active' => true,
        ]);

        $this->agoda = Channel::create([
            'name' => 'Agoda YCS Global',
            'code' => 'AGODA',
            'commission_rate' => 14.00,
            'is_active' => true,
        ]);

        ChannelConnection::create([
            'hotel_id' => $this->hotel->id,
            'channel_id' => $this->bookingCom->id,
            'channel_hotel_identifier' => 'BKG-9921',
            'is_enabled' => true,
            'is_sandbox' => true,
            'sync_inventory' => true,
            'sync_rates' => true,
            'sync_restrictions' => true,
        ]);

        ChannelConnection::create([
            'hotel_id' => $this->hotel->id,
            'channel_id' => $this->agoda->id,
            'channel_hotel_identifier' => 'AGD-5510',
            'is_enabled' => true,
            'is_sandbox' => true,
            'sync_inventory' => true,
            'sync_rates' => true,
            'sync_restrictions' => true,
        ]);

        $this->manager = User::create([
            'name' => 'Kenji Tanaka (Revenue Manager)',
            'email' => 'tanaka.revenue@clearstay.jp',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'department' => 'Revenue & Distribution',
            'locale' => 'ja',
            'is_active' => true,
        ]);

        $this->receptionist = User::create([
            'name' => 'Aoi Sato (Front Desk)',
            'email' => 'sato.frontdesk@clearstay.jp',
            'password' => Hash::make('password123'),
            'role' => 'frontdesk',
            'hotel_id' => $this->hotel->id,
            'department' => 'Front Office',
            'locale' => 'ja',
            'is_active' => true,
        ]);

        $this->inventoryService = app(CentralInventoryService::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 9.1: INVENTORY INITIALIZATION & CONCURRENCY TESTS
    |--------------------------------------------------------------------------
    */

    public function test_inventory_initialization_is_idempotent_and_preserves_existing_data(): void
    {
        $testDate = '2026-11-20';

        // 1. Pre-create a customized inventory row
        $customRow = CentralInventory::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'date' => $testDate,
            'total_units' => 10,
            'available_units' => 4,
            'reserved_units' => 5,
            'maintenance_units' => 1,
            'base_rate' => 45000.00,
            'currency' => 'JPY',
            'min_stay' => 3,
            'max_stay' => 14,
            'stop_sell' => true,
            'version' => 3,
        ]);

        $this->assertEquals(4, $customRow->calculateAvailableUnits());

        // 2. Run ensureUpcomingInventory (90 days)
        $created = $this->inventoryService->ensureUpcomingInventory($this->hotel->id, 90);
        $this->assertGreaterThan(0, $created);

        // 3. Confirm only ONE record exists for this hotel + room_type + date
        $records = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->roomType->id)
            ->whereDate('date', $testDate)
            ->get();

        $this->assertCount(1, $records);

        // 4. Verify existing record was NOT overwritten by seeder defaults
        $reloaded = $records->first();
        $this->assertEquals(45000.00, $reloaded->base_rate, 'Custom rate was preserved');
        $this->assertEquals(5, $reloaded->reserved_units, 'Reserved units preserved');
        $this->assertEquals(1, $reloaded->maintenance_units, 'Maintenance units preserved');
        $this->assertEquals(4, $reloaded->available_units, 'Availability preserved');
        $this->assertTrue($reloaded->stop_sell, 'Stop sell flag preserved');
        $this->assertEquals(3, $reloaded->version, 'Version preserved');

        // 5. Run initialization again (2nd time) - MUST be strictly idempotent
        $createdSecondTime = $this->inventoryService->ensureUpcomingInventory($this->hotel->id, 90);
        $this->assertEquals(0, $createdSecondTime, 'No duplicate records created on second run');

        $countAfterSecond = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->roomType->id)
            ->whereDate('date', $testDate)
            ->count();
        $this->assertEquals(1, $countAfterSecond);
    }

    public function test_composite_unique_constraint_enforced_at_database_level(): void
    {
        $date = '2026-11-25';

        CentralInventory::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'date' => $date,
            'total_units' => 10,
            'available_units' => 10,
            'reserved_units' => 0,
            'maintenance_units' => 0,
            'base_rate' => 32000,
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        // Attempting direct duplicate insertion must be rejected by database unique index
        CentralInventory::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'date' => $date,
            'total_units' => 10,
            'available_units' => 10,
            'reserved_units' => 0,
            'maintenance_units' => 0,
            'base_rate' => 35000,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 9.2: FULL CHANNEL AUDIT TESTS
    |--------------------------------------------------------------------------
    */

    public function test_full_channel_audit_executes_without_mutating_central_inventory(): void
    {
        // 1. Ensure 14 days of central inventory exist
        $this->inventoryService->ensureUpcomingInventory($this->hotel->id, 14);

        $initialCentralInventory = CentralInventory::where('hotel_id', $this->hotel->id)
            ->orderBy('id')
            ->get(['id', 'hotel_id', 'room_type_id', 'date', 'available_units', 'base_rate', 'reserved_units', 'version'])
            ->toArray();

        $initialCentralCount = count($initialCentralInventory);
        $this->assertGreaterThan(0, $initialCentralCount);

        // 2. Execute Full Channel Audit via POST
        $response = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->post(route('inventory_reconciliation.audit'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 3. CRITICAL: Central inventory table MUST be 100% unchanged!
        $afterCentralInventory = CentralInventory::where('hotel_id', $this->hotel->id)
            ->orderBy('id')
            ->get(['id', 'hotel_id', 'room_type_id', 'date', 'available_units', 'base_rate', 'reserved_units', 'version'])
            ->toArray();

        $this->assertCount($initialCentralCount, $afterCentralInventory);
        $this->assertEquals($initialCentralInventory, $afterCentralInventory, 'Central inventory was not mutated by audit');

        // 4. Audit evidence was recorded in inventory_audit_runs
        $auditRun = InventoryAuditRun::where('hotel_id', $this->hotel->id)->latest()->first();
        $this->assertNotNull($auditRun);
        $this->assertStringStartsWith('audit_', $auditRun->audit_uuid);
        $this->assertGreaterThan(0, $auditRun->total_pairs_checked);
        $this->assertGreaterThanOrEqual(0, $auditRun->matching_pairs);
        $this->assertNotNull($auditRun->duration_ms);

        // 5. Findings recorded in inventory_reconciliations table
        $findingsCount = InventoryReconciliation::where('hotel_id', $this->hotel->id)->count();
        $this->assertGreaterThan(0, $findingsCount);

        // 6. Test repeat audit creates NEW audit run without duplicate IDs or breaking
        $response2 = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->post(route('inventory_reconciliation.audit'));

        $response2->assertRedirect();
        $auditRunsCount = InventoryAuditRun::where('hotel_id', $this->hotel->id)->count();
        $this->assertEquals(2, $auditRunsCount, 'Multiple audit runs preserved in audit run history');
    }

    public function test_audit_detects_overselling_risk_and_discrepancies(): void
    {
        $this->inventoryService->ensureUpcomingInventory($this->hotel->id, 14);

        $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->post(route('inventory_reconciliation.audit'));

        $oversellRisks = InventoryReconciliation::where('hotel_id', $this->hotel->id)
            ->where('status', 'oversell_risk')
            ->get();

        if ($oversellRisks->isNotEmpty()) {
            $sample = $oversellRisks->first();
            $this->assertGreaterThan($sample->central_units, $sample->channel_units, 'Overselling risk occurs when channel > central');
            $this->assertStringContainsString('CRITICAL', $sample->suggested_action);
        }

        // Test filtering by oversell_risk
        $filterResponse = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->get(route('inventory_reconciliation.index', ['status' => 'oversell_risk']));

        $filterResponse->assertOk();
        $filterResponse->assertSee('Oversell Risk');
    }

    public function test_reconciliation_json_and_csv_exports(): void
    {
        $this->inventoryService->ensureUpcomingInventory($this->hotel->id, 14);

        $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->post(route('inventory_reconciliation.audit'));

        // 1. JSON Export
        $jsonResponse = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->get(route('inventory_reconciliation.export', ['format' => 'json']));

        $jsonResponse->assertOk();
        $jsonResponse->assertJsonStructure([
            'hotel_id',
            'exported_at',
            'total_records',
            'findings' => [
                '*' => ['id', 'stay_date', 'room_type', 'channel', 'central_units', 'channel_units', 'difference', 'status']
            ]
        ]);

        // 2. CSV Export
        $csvResponse = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->get(route('inventory_reconciliation.export', ['format' => 'csv']));

        $csvResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csvResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="clearstay_inventory_audit_', (string) $csvResponse->headers->get('Content-Disposition'));
    }

    public function test_resolving_discrepancy_realigns_channel_and_dispatches_outbox(): void
    {
        $stayDate = Carbon::today()->addDays(3)->format('Y-m-d');

        // Central inventory record
        $central = CentralInventory::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'date' => $stayDate,
            'total_units' => 10,
            'available_units' => 7,
            'reserved_units' => 3,
            'maintenance_units' => 0,
            'base_rate' => 32000,
        ]);

        // Reconciliation record with drift
        $recon = InventoryReconciliation::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'channel_id' => $this->bookingCom->id,
            'stay_date' => $stayDate,
            'central_units' => 7,
            'channel_units' => 9, // Overselling risk!
            'difference' => -2,
            'status' => 'oversell_risk',
        ]);

        $outboxBefore = SyncOutboxEvent::count();

        // Resolve discrepancy
        $resolveRes = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->post(route('inventory_reconciliation.resolve', $recon));

        $resolveRes->assertRedirect();
        $resolveRes->assertSessionHas('success');

        $recon->refresh();
        $this->assertEquals('resolved', $recon->status);
        $this->assertEquals(0, $recon->difference);
        $this->assertEquals(7, $recon->channel_units, 'Channel units aligned to central units');
        $this->assertNotNull($recon->resolved_at);

        // Outbox event was queued
        $outboxAfter = SyncOutboxEvent::count();
        $this->assertGreaterThan($outboxBefore, $outboxAfter);

        $outboxEvent = SyncOutboxEvent::latest()->first();
        $this->assertEquals($this->hotel->id, $outboxEvent->hotel_id);
        $this->assertEquals('reconciliation_realignment', $outboxEvent->event_type);
    }

    /*
    |--------------------------------------------------------------------------
    | SECTION 9.3: BULK CHANGE STUDIO TESTS
    |--------------------------------------------------------------------------
    */

    public function test_bulk_editor_get_request_is_strictly_read_only(): void
    {
        $countBefore = CentralInventory::where('hotel_id', $this->hotel->id)->count();

        $response = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->get(route('bulk_editor.index'));

        $response->assertOk();
        $response->assertViewIs('bulk_editor.index');

        $countAfter = CentralInventory::where('hotel_id', $this->hotel->id)->count();
        $this->assertEquals($countBefore, $countAfter, 'GET request made zero inventory insertions');
    }

    public function test_bulk_preview_does_not_mutate_database(): void
    {
        $startDate = Carbon::today()->format('Y-m-d');
        $endDate = Carbon::today()->addDays(5)->format('Y-m-d');

        $this->inventoryService->ensureUpcomingInventory($this->hotel->id, 7);

        $beforeState = CentralInventory::where('hotel_id', $this->hotel->id)->get()->toArray();

        $response = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->postJson(route('bulk_editor.preview'), [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'room_type_id' => $this->roomType->id,
                'price_delta' => '+5000',
                'available_units' => 3,
                'stop_sell' => 1,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'preview' => [
                'total_days',
                'total_records_affected',
                'room_types_affected',
                'channels_count',
                'sample_items',
                'warnings',
            ]
        ]);

        $afterState = CentralInventory::where('hotel_id', $this->hotel->id)->get()->toArray();
        $this->assertEquals($beforeState, $afterState, 'Preview did NOT mutate database');
    }

    public function test_bulk_commit_applies_changes_and_records_audit_and_outbox(): void
    {
        $startDate = Carbon::today()->format('Y-m-d');
        $endDate = Carbon::today()->addDays(3)->format('Y-m-d');

        $this->inventoryService->ensureUpcomingInventory($this->hotel->id, 7);

        $outboxBefore = SyncOutboxEvent::count();
        $batchesBefore = BulkChangeBatch::count();

        $response = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->post(route('bulk_editor.commit'), [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'room_type_id' => $this->roomType->id,
                'price_delta' => '+3000',
                'available_units' => 2,
                'min_stay' => 2,
                'reason' => 'High season festival tariff rate adjustment',
            ]);

        $response->assertRedirect(route('bulk_editor.index'));
        $response->assertSessionHas('success');

        // 1. Verify Batch record created
        $this->assertEquals($batchesBefore + 1, BulkChangeBatch::count());
        $batch = BulkChangeBatch::latest()->first();
        $this->assertEquals('applied', $batch->status);
        $this->assertStringContainsString('High season festival', $batch->summary);

        // 2. Verify database records updated
        $updatedRecords = CentralInventory::where('hotel_id', $this->hotel->id)
            ->where('room_type_id', $this->roomType->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();

        foreach ($updatedRecords as $rec) {
            $this->assertEquals(2, $rec->available_units);
            $this->assertEquals(2, $rec->min_stay);
            $this->assertGreaterThan(32000, $rec->base_rate);
        }

        // 3. Verify channel outbox event created
        $outboxAfter = SyncOutboxEvent::count();
        $this->assertGreaterThan($outboxBefore, $outboxAfter);

        // 4. Test 1-Click Rollback
        $rollbackRes = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->post(route('bulk_editor.rollback', $batch));

        $rollbackRes->assertRedirect(route('bulk_editor.index'));
        $rollbackRes->assertSessionHas('success');

        $batch->refresh();
        $this->assertEquals('rolled_back', $batch->status);
        $this->assertNotNull($batch->rolled_back_at);

        // 5. Repeated rollback prevention
        $repeatRollbackRes = $this->actingAs($this->manager)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->post(route('bulk_editor.rollback', $batch));

        $repeatRollbackRes->assertSessionHas('error');
    }
}
