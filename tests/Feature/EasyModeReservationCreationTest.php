<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EasyModeReservationCreationTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $user;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::firstOrCreate(['code' => 'TYO-GNZ'], [
            'name' => 'ClearStay Premier Tokyo Ginza',
            'slug' => 'clearstay-premier-tokyo-ginza',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'currency' => 'JPY',
            'is_active' => true,
        ]);

        $this->user = User::firstOrCreate(['email' => 'easymode.test@clearstay.jp'], [
            'name' => 'Easy Mode Tester',
            'password' => bcrypt('password'),
            'role' => 'frontdesk',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $roomType = RoomType::firstOrCreate(['hotel_id' => $this->hotel->id, 'code' => 'SGL-TEST'], [
            'name' => 'Standard Single',
            'capacity' => 1,
            'base_price' => 12000,
        ]);

        $this->room = Room::firstOrCreate(['hotel_id' => $this->hotel->id, 'room_number' => '101'], [
            'room_type_id' => $roomType->id,
            'floor' => 1,
            'status' => 'clean',
            'is_occupied' => false,
            'is_active' => true,
        ]);
    }

    public function test_easy_mode_toggle_switches_session_state(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('easy_mode.toggle'));

        $response->assertRedirect(route('easy_mode.index'));
        $this->assertTrue(session('clearstay_easy_mode'));

        // Toggle back to standard
        $toggleBack = $this->actingAs($this->user)
            ->post(route('easy_mode.toggle'));

        $toggleBack->assertRedirect(route('overview.index'));
        $this->assertFalse(session('clearstay_easy_mode'));
    }

    public function test_easy_mode_reservation_create_page_renders(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('easy_mode.create'));

        $response->assertStatus(200);
        $response->assertSee('新規予約の入力');
        $response->assertSee('101号室');
    }

    public function test_can_create_real_reservation_in_easy_mode(): void
    {
        $checkIn = Carbon::today()->toDateString();
        $checkOut = Carbon::tomorrow()->toDateString();

        $response = $this->actingAs($this->user)
            ->post(route('easy_mode.store'), [
                'guest_name' => 'Ichiro Suzuki',
                'guest_phone' => '+81-90-1234-9999',
                'room_id' => $this->room->id,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
            ]);

        $response->assertRedirect(route('easy_mode.reservations'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('guests', [
            'first_name' => 'Ichiro',
            'last_name' => 'Suzuki',
        ]);
    }
}
