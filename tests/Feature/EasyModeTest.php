<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EasyModeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Hotel $hotel;
    protected RoomType $roomType;
    protected Room $room;
    protected Guest $guest;

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
            'total_rooms' => 20,
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Kenji Sato',
            'email' => 'kenji.sato@clearstay.test',
            'password' => bcrypt('Secret123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'department' => 'Operations',
            'locale' => 'ja',
            'is_active' => true,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Standard Double',
            'code' => 'STD-DBL',
            'base_price' => 15000,
            'capacity' => 2,
            'total_rooms' => 10,
        ]);

        $this->room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'clean',
        ]);

        $this->guest = Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Kenji',
            'last_name' => 'Tanaka',
            'email' => 'tanaka@example.com',
            'phone' => '090-1234-5678',
        ]);
    }

    public function test_easy_mode_hub_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)->get('/easy-mode');

        $response->assertStatus(200);
        $response->assertSee('かんたん操作');
        $response->assertSee('チェックイン');
        $response->assertSee('チェックアウト');
        $response->assertSee('客室の清掃状態');
    }

    public function test_easy_mode_arrivals_and_check_in_workflow(): void
    {
        $reservation = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room->id,
            'room_type_id' => $this->roomType->id,
            'confirmation_code' => 'BK-EM-ARRIVE',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'status' => 'confirmed',
            'total_price' => 30000,
            'nights' => 2,
            'adults' => 2,
            'children' => 0,
        ]);

        $arrivalsResponse = $this->actingAs($this->user)->get('/easy-mode/arrivals');
        $arrivalsResponse->assertStatus(200);
        $arrivalsResponse->assertSee($this->guest->full_name);
        $arrivalsResponse->assertSee('BK-EM-ARRIVE');

        $checkInResponse = $this->actingAs($this->user)->post("/easy-mode/check-in/{$reservation->id}");
        $checkInResponse->assertRedirect('/easy-mode/arrivals');
        $checkInResponse->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'checked_in',
        ]);

        $this->assertTrue((bool)$this->room->fresh()->is_occupied);
    }

    public function test_easy_mode_departures_and_check_out_workflow(): void
    {
        $this->room->update(['status' => 'occupied']);

        $reservation = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room->id,
            'room_type_id' => $this->roomType->id,
            'confirmation_code' => 'BK-EM-DEPART',
            'check_in_date' => now()->subDays(2)->toDateString(),
            'check_out_date' => now()->toDateString(),
            'status' => 'checked_in',
            'total_price' => 30000,
            'nights' => 2,
            'adults' => 2,
            'children' => 0,
        ]);

        $departuresResponse = $this->actingAs($this->user)->get('/easy-mode/departures');
        $departuresResponse->assertStatus(200);
        $departuresResponse->assertSee($this->guest->full_name);

        $checkOutResponse = $this->actingAs($this->user)->post("/easy-mode/check-out/{$reservation->id}");
        $checkOutResponse->assertRedirect('/easy-mode/departures');
        $checkOutResponse->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'checked_out',
        ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $this->room->id,
            'status' => 'dirty',
        ]);
    }

    public function test_easy_mode_room_status_toggle(): void
    {
        $this->assertEquals('clean', $this->room->fresh()->status);

        $toggleResponse = $this->actingAs($this->user)->post("/easy-mode/rooms/{$this->room->id}/status", [
            'status' => 'dirty',
        ]);
        $toggleResponse->assertRedirect('/easy-mode/rooms');

        $this->assertEquals('dirty', $this->room->fresh()->status);

        $toggleBack = $this->actingAs($this->user)->post("/easy-mode/rooms/{$this->room->id}/status", [
            'status' => 'clean',
        ]);
        $toggleBack->assertRedirect('/easy-mode/rooms');

        $this->assertEquals('clean', $this->room->fresh()->status);
    }

    public function test_easy_mode_reservation_search(): void
    {
        Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room->id,
            'room_type_id' => $this->roomType->id,
            'confirmation_code' => 'BK-SEARCH-TARGET',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'status' => 'confirmed',
            'total_price' => 30000,
            'nights' => 2,
            'adults' => 2,
            'children' => 0,
        ]);

        $searchResponse = $this->actingAs($this->user)->get('/easy-mode/reservations?q=Tanaka');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('BK-SEARCH-TARGET');
        $searchResponse->assertSee('Kenji Tanaka');
    }

    public function test_easy_mode_session_toggle(): void
    {
        $response = $this->actingAs($this->user)->post('/easy-mode/toggle');
        $response->assertRedirect();
        $this->assertTrue(session('clearstay_easy_mode'));

        $toggleOff = $this->actingAs($this->user)->post('/easy-mode/toggle');
        $toggleOff->assertRedirect();
        $this->assertFalse(session('clearstay_easy_mode'));
    }

    public function test_easy_mode_empty_search_returns_all_reservations(): void
    {
        Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room->id,
            'room_type_id' => $this->roomType->id,
            'confirmation_code' => 'BK-ALL-RESULTS',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'status' => 'confirmed',
            'total_price' => 30000,
            'nights' => 2,
            'adults' => 2,
            'children' => 0,
        ]);

        $response = $this->actingAs($this->user)->get('/easy-mode/reservations');
        $response->assertStatus(200);
        $response->assertSee('BK-ALL-RESULTS');
    }

    public function test_easy_mode_invalid_room_status_validation(): void
    {
        $response = $this->actingAs($this->user)->post("/easy-mode/rooms/{$this->room->id}/status", [
            'status' => 'invalid_status_xyz',
        ]);

        $response->assertSessionHasErrors('status');
    }
}
