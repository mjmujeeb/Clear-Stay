<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Revenue\OverbookingIntelligenceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlledOverbookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected Hotel $hotel;
    protected RoomType $roomType;
    protected Room $room1;
    protected Room $room2;
    protected OverbookingIntelligenceService $overbookingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Tokyo Ginza',
            'slug' => 'clearstay-tokyo-ginza',
            'code' => 'TYO-GNZ',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'total_rooms' => 50,
            'is_active' => true,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Standard Double',
            'code' => 'STD-DBL',
            'base_price' => 18500.00,
            'capacity' => 2,
        ]);

        $this->room1 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'clean',
            'is_occupied' => false,
        ]);

        $this->room2 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '102',
            'floor' => 1,
            'status' => 'clean',
            'is_occupied' => false,
        ]);

        $this->manager = User::create([
            'name' => 'Kenji Sato',
            'email' => 'kenji.sato@clearstay.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'locale' => 'en',
            'is_active' => true,
        ]);

        $this->overbookingService = app(OverbookingIntelligenceService::class);
    }

    public function test_overbooking_calculation_evaluates_three_strategies()
    {
        $response = $this->actingAs($this->manager)
            ->getJson('/reservation-integrity/calculate-overbooking?capacity=50&cancellation_rate=14.5&noshow_rate=4.2&adr=18500&walk_cost=35000');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'inputs',
            'strategies' => [
                'zero_overbooking' => ['booking_limit', 'expected_revenue', 'walk_risk_pct'],
                'fixed_buffer' => ['booking_limit', 'net_profit', 'walk_risk_pct'],
                'stochastic_optimization' => ['booking_limit', 'buffer_rooms', 'net_profit', 'walk_risk_pct', 'is_safe'],
            ],
            'safety_stop_triggered',
            'academic_references',
        ]);

        $data = $response->json();
        $this->assertEquals(50, $data['strategies']['zero_overbooking']['booking_limit']);
        $this->assertGreaterThan(50, $data['strategies']['stochastic_optimization']['booking_limit']);
    }

    public function test_manager_can_update_overbooking_policy_and_it_records_audit_log()
    {
        $response = $this->actingAs($this->manager)
            ->post('/reservation-integrity/policy', [
                'is_enabled' => 1,
                'strategy' => 'stochastic_optimization',
                'max_buffer_rooms' => 3,
                'default_adr' => 20000,
                'relocation_cost_per_walk' => 40000,
                'historical_cancellation_rate' => 15.0,
                'historical_noshow_rate' => 5.0,
                'max_tolerated_walk_risk' => 2.5,
                'policy_notes' => 'Authorized for upcoming Golden Week demand surge.',
            ]);

        $response->assertRedirect();
        $policy = $this->overbookingService->getPolicy($this->hotel);
        $this->assertTrue($policy->is_enabled);
        $this->assertEquals('stochastic_optimization', $policy->strategy);
        $this->assertEquals(3, $policy->max_buffer_rooms);

        // Verify Audit Log
        $audit = AuditLog::where('hotel_id', $this->hotel->id)
            ->where('action', 'OVERBOOKING_POLICY_UPDATED')
            ->first();
        $this->assertNotNull($audit);
    }

    public function test_reservation_recovery_reassigns_room_with_audit_trail()
    {
        $guest = \App\Models\Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Recovery',
            'last_name' => 'Guest',
            'email' => 'recovery@example.test',
            'phone' => '+81 90-1111-2222',
        ]);

        $res = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $guest->id,
            'room_id' => $this->room1->id,
            'confirmation_code' => 'CS-RECOVER-101',
            'check_in_date' => Carbon::tomorrow()->toDateString(),
            'check_out_date' => Carbon::tomorrow()->addDay()->toDateString(),
            'adults' => 1,
            'children' => 0,
            'total_price' => 18500.00,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->manager)
            ->post('/reservation-integrity/reassign', [
                'reservation_id' => $res->id,
                'target_room_id' => $this->room2->id,
                'justification' => 'Room 101 AC unit maintenance required. Moved guest to Room 102.',
            ]);

        $response->assertRedirect();
        $res->refresh();
        $this->assertEquals($this->room2->id, $res->room_id);
        $this->assertStringContainsString('Room 101 AC unit maintenance', $res->special_requests);

        // Verify Audit Log
        $audit = AuditLog::where('hotel_id', $this->hotel->id)
            ->where('action', 'ROOM_REASSIGNED_RECOVERY')
            ->first();
        $this->assertNotNull($audit);
    }
}
