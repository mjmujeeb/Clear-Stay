<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\InventoryLedgerEntry;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EasyModeCheckinWizardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Hotel $hotel;
    protected RoomType $roomType;
    protected Room $room;
    protected Guest $guest;
    protected Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Grand Tokyo',
            'slug' => 'clearstay-grand-tokyo',
            'code' => 'CS-TOKYO',
            'email' => 'tokyo@clearstay.jp',
            'phone' => '+81-3-1111-2222',
            'address' => 'Chiyoda-ku, Tokyo',
            'city' => 'Tokyo',
            'country' => 'Japan',
        ]);

        $this->user = User::create([
            'name' => 'Front Desk Staff',
            'email' => 'frontdesk@clearstay.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'locale' => 'ja',
            'is_active' => true,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe King',
            'code' => 'DLX-K',
            'base_price' => 22000,
            'capacity' => 2,
        ]);

        $this->room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '301',
            'floor' => 3,
            'status' => 'clean',
            'is_occupied' => false,
        ]);

        $this->guest = Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Kenji',
            'last_name' => 'Sato',
            'email' => 'kenji.sato@example.jp',
            'phone' => '+81-90-1234-5678',
            'nationality' => 'Japan',
            'passport_or_id' => 'AB1234567',
        ]);

        $this->reservation = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room->id,
            'confirmation_code' => 'CS-WIZ-1001',
            'guest_portal_token' => 'token-wiz-1001',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'total_price' => 44000,
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'source' => 'direct',
        ]);
    }

    public function test_checkin_wizard_page_loads_with_all_5_steps(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->get(route('easy_mode.checkin_wizard'));

        $response->assertStatus(200);
        $response->assertSee('5ステップ・かんたんチェックイン案内');
        $response->assertSee('panel-step-1', false);
        $response->assertSee('panel-step-2', false);
        $response->assertSee('panel-step-3', false);
        $response->assertSee('panel-step-4', false);
        $response->assertSee('panel-step-5', false);
    }

    public function test_checkin_wizard_with_preselected_reservation(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->get(route('easy_mode.checkin_wizard', ['reservation_id' => $this->reservation->id]));

        $response->assertStatus(200);
        $response->assertSee('CS-WIZ-1001');
        $response->assertSee('Kenji Sato');
        $response->assertSee('301');
    }

    public function test_checkin_wizard_live_search_endpoint_returns_json(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->getJson(route('easy_mode.checkin_wizard.search', ['q' => 'Kenji']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results' => [
                '*' => [
                    'id',
                    'confirmation_code',
                    'guest_name',
                    'phone',
                    'passport_or_id',
                    'room_number',
                    'check_in_date',
                    'check_out_date',
                    'nights',
                    'total_price',
                    'status',
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'confirmation_code' => 'CS-WIZ-1001',
        ]);
    }

    public function test_complete_checkin_wizard_transaction_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->postJson(route('easy_mode.checkin_wizard.complete'), [
                'reservation_id' => $this->reservation->id,
                'room_id' => $this->room->id,
                'keycards_issued' => 2,
                'passport_or_id' => 'PASS-UPDATED-999',
                'guest_phone' => '+81-80-9999-8888',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'already_checked_in' => false,
        ]);

        // Verify Reservation Status
        $this->reservation->refresh();
        $this->assertEquals('checked_in', $this->reservation->status);
        $this->assertNotNull($this->reservation->checked_in_at);
        $this->assertStringContainsString('2 keycard(s) issued', $this->reservation->special_requests);

        // Verify Room Occupancy
        $this->room->refresh();
        $this->assertTrue((bool)$this->room->is_occupied);

        // Verify Guest Updated
        $this->guest->refresh();
        $this->assertEquals('PASS-UPDATED-999', $this->guest->passport_or_id);
        $this->assertEquals('+81-80-9999-8888', $this->guest->phone);

        // Verify Inventory Ledger Entry Created
        $this->assertDatabaseHas('inventory_ledger_entries', [
            'hotel_id' => $this->hotel->id,
            'operation_type' => 'CHECK_IN_OCCUPIED',
            'source' => 'EASY_MODE_WIZARD',
        ]);
    }

    public function test_complete_checkin_wizard_is_idempotent_on_already_checked_in(): void
    {
        $this->reservation->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['current_hotel_id' => $this->hotel->id])
            ->postJson(route('easy_mode.checkin_wizard.complete'), [
                'reservation_id' => $this->reservation->id,
                'room_id' => $this->room->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'already_checked_in' => true,
        ]);
    }
}
