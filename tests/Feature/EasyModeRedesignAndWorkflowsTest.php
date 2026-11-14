<?php

namespace Tests\Feature;

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

class EasyModeRedesignAndWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $user;
    protected RoomType $roomType;
    protected Room $room1;
    protected Room $room2;
    protected Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Ginza Premier',
            'slug' => 'clearstay-ginza-premier',
            'code' => 'CS-GZ-PREM',
            'address' => '5-7-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'total_rooms' => 15,
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Yuki Takahashi',
            'email' => 'yuki.takahashi@clearstay.test',
            'password' => bcrypt('SecretPass123!'),
            'role' => 'receptionist',
            'hotel_id' => $this->hotel->id,
            'department' => 'Front Desk',
            'locale' => 'ja',
            'is_active' => true,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe King',
            'code' => 'DLX-KNG',
            'base_price' => 22000,
            'capacity' => 2,
            'total_rooms' => 10,
        ]);

        $this->room1 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '201',
            'floor' => 2,
            'status' => 'clean',
            'is_occupied' => false,
        ]);

        $this->room2 = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '202',
            'floor' => 2,
            'status' => 'dirty',
            'is_occupied' => false,
        ]);

        $this->guest = Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'email' => 'taro.yamada@example.jp',
            'phone' => '+81-90-5555-1234',
        ]);
    }

    /**
     * Test 1: Easy Mode Home Hub Displays Brand and Time-Aware Header
     */
    public function test_easy_mode_hub_displays_brand_and_time_aware_greeting(): void
    {
        $response = $this->actingAs($this->user)->get(route('easy_mode.index'));

        $response->assertOk();
        $response->assertSee('ClearStay Easy Mode — Simple Hotel Operations');
        $response->assertSee('ClearStay かんたん操作');
        $response->assertSee('Yuki Takahashi');
        $response->assertViewHas('greetingJa');
        $response->assertViewHas('greetingEn');
        $response->assertViewHas('nextTask');
    }

    /**
     * Test 2: Next Recommended Task prioritizes arrivals waiting when clean rooms available
     */
    public function test_next_recommended_task_recommends_checkin_when_arrivals_waiting(): void
    {
        Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room1->id,
            'room_type_id' => $this->roomType->id,
            'confirmation_code' => 'CS-REC-001',
            'check_in_date' => Carbon::today()->toDateString(),
            'check_out_date' => Carbon::tomorrow()->toDateString(),
            'status' => 'confirmed',
            'total_price' => 22000,
            'nights' => 1,
            'adults' => 2,
            'children' => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('easy_mode.index'));

        $response->assertOk();
        $nextTask = $response->viewData('nextTask');
        $this->assertEquals('checkin', $nextTask['type']);
        $this->assertStringContainsString('チェックイン', $nextTask['title_ja']);
        $this->assertStringContainsString('201', $nextTask['title_ja']);
    }

    /**
     * Test 3: Next Recommended Task recommends checkout when departures are pending
     */
    public function test_next_recommended_task_recommends_checkout_when_departures_pending(): void
    {
        // No arrivals today, but 1 departure
        Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room1->id,
            'room_type_id' => $this->roomType->id,
            'confirmation_code' => 'CS-DEP-001',
            'check_in_date' => Carbon::yesterday()->toDateString(),
            'check_out_date' => Carbon::today()->toDateString(),
            'status' => 'checked_in',
            'total_price' => 22000,
            'nights' => 1,
            'adults' => 1,
            'children' => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('easy_mode.index'));

        $response->assertOk();
        $nextTask = $response->viewData('nextTask');
        $this->assertEquals('checkout', $nextTask['type']);
        $this->assertStringContainsString('チェックアウト', $nextTask['title_ja']);
    }

    /**
     * Test 4: Role workspace filtering (receptionist, housekeeping, manager, all)
     */
    public function test_role_workspace_filtering_filters_tiles_properly(): void
    {
        // Receptionist view
        $recResponse = $this->actingAs($this->user)->get(route('easy_mode.index', ['role' => 'receptionist']));
        $recResponse->assertOk();
        $recResponse->assertSee('5ステップ・チェックイン案内');
        $recResponse->assertSee('本日の到着一覧・受付');

        // Housekeeping view
        $hkResponse = $this->actingAs($this->user)->get(route('easy_mode.index', ['role' => 'housekeeping']));
        $hkResponse->assertOk();
        $hkResponse->assertSee('客室の清掃状態・清掃管理');
    }

    /**
     * Test 5: 5-Step Check-In Wizard live search and completion transaction
     */
    public function test_checkin_wizard_search_and_completion(): void
    {
        $res = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room2->id,
            'room_type_id' => $this->roomType->id,
            'confirmation_code' => 'CS-WIZ-999',
            'check_in_date' => Carbon::today()->toDateString(),
            'check_out_date' => Carbon::tomorrow()->toDateString(),
            'status' => 'confirmed',
            'total_price' => 22000,
            'nights' => 1,
            'adults' => 1,
            'children' => 0,
        ]);

        // 1. Live search API
        $searchResponse = $this->actingAs($this->user)->get(route('easy_mode.checkin_wizard.search', ['q' => 'Yamada']));
        $searchResponse->assertOk();
        $results = $searchResponse->json('results');
        $this->assertCount(1, $results);
        $this->assertEquals('CS-WIZ-999', $results[0]['confirmation_code']);

        // 2. Complete check-in wizard
        $completeResponse = $this->actingAs($this->user)->post(route('easy_mode.checkin_wizard.complete'), [
            'reservation_id' => $res->id,
            'room_id' => $this->room1->id,
            'keycards_issued' => 2,
            'passport_or_id' => 'PASS-JPN-8877',
            'guest_phone' => '+81-90-9999-8888',
        ]);

        $completeResponse->assertRedirect();
        $this->assertEquals('checked_in', $res->fresh()->status);
        $this->assertEquals($this->room1->id, $res->fresh()->room_id);
        $this->assertTrue((bool)$this->room1->fresh()->is_occupied);
        $this->assertStringContainsString('2 keycard(s) issued', $res->fresh()->special_requests);
        $this->assertEquals('PASS-JPN-8877', $this->guest->fresh()->passport_or_id);
    }

    /**
     * Test 6: 1-Click Checkout sets room dirty and creates high-priority housekeeping turnover task
     */
    public function test_checkout_creates_housekeeping_task_and_marks_room_dirty(): void
    {
        $this->room1->update(['is_occupied' => true, 'status' => 'clean']);

        $res = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room1->id,
            'room_type_id' => $this->roomType->id,
            'confirmation_code' => 'CS-CHECKOUT-TEST',
            'check_in_date' => Carbon::yesterday()->toDateString(),
            'check_out_date' => Carbon::today()->toDateString(),
            'status' => 'checked_in',
            'total_price' => 22000,
            'nights' => 1,
            'adults' => 1,
            'children' => 0,
        ]);

        $response = $this->actingAs($this->user)->post(route('easy_mode.check_out', $res->id));
        $response->assertRedirect(route('easy_mode.departures'));
        $response->assertSessionHas('success');

        $this->assertEquals('checked_out', $res->fresh()->status);
        $this->assertFalse((bool)$this->room1->fresh()->is_occupied);
        $this->assertEquals('dirty', $this->room1->fresh()->status);

        // Verify HousekeepingTask created
        $task = HousekeepingTask::where('hotel_id', $this->hotel->id)
            ->where('room_id', $this->room1->id)
            ->first();

        $this->assertNotNull($task);
        $this->assertEquals('checkout_turnover', $task->task_type);
        $this->assertEquals('high', $task->priority);
        $this->assertEquals('pending', $task->status);
    }
}
