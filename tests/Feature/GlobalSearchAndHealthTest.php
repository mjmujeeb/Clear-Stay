<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\IntegrationPartner;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchAndHealthTest extends TestCase
{
    use RefreshDatabase;

    protected Hotel $hotel;
    protected User $manager;
    protected RoomType $roomType;
    protected Room $room;
    protected Guest $guest;
    protected Reservation $reservation;
    protected Channel $channel;
    protected IntegrationPartner $partner;

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

        $this->manager = User::create([
            'name' => 'Kenji Sato',
            'email' => 'kenji.sato@clearstay.com',
            'password' => bcrypt('Password123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);

        $this->roomType = RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe King Suite',
            'code' => 'DLX-KNG',
            'base_price' => 25000,
            'max_occupancy' => 2,
            'total_rooms' => 10,
        ]);

        $this->room = Room::create([
            'hotel_id' => $this->hotel->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '501',
            'floor' => 5,
            'status' => 'clean',
            'is_occupied' => false,
        ]);

        $this->guest = Guest::create([
            'hotel_id' => $this->hotel->id,
            'first_name' => 'Taro',
            'last_name' => 'Tanaka',
            'email' => 'tanaka.taro@japan-corp.jp',
            'phone' => '+81-90-1122-3344',
        ]);

        $this->reservation = Reservation::create([
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'guest_id' => $this->guest->id,
            'confirmation_code' => 'CS-SEARCH-999',
            'check_in_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
            'check_out_date' => Carbon::today()->addDays(7)->format('Y-m-d'),
            'adults' => 2,
            'children' => 0,
            'total_price' => 50000,
            'status' => 'confirmed',
            'source' => 'direct',
        ]);

        $this->channel = Channel::create([
            'name' => 'Booking.com Global',
            'slug' => 'booking-com-global',
            'code' => 'BDC-GLB',
            'type' => 'ota',
            'is_active' => true,
        ]);

        $this->partner = IntegrationPartner::create([
            'name' => 'Temairazu Hotel Connectivity Suite',
            'company' => 'Temairazu, Inc.',
            'category' => 'channel_manager',
            'api_base_url' => 'https://api.temairazu.com/v3',
            'api_version' => 'v3.2',
            'auth_type' => 'oauth2',
            'status' => 'certified',
            'contact_email' => 'api-support@temairazu.com',
        ]);
    }

    public function test_api_search_returns_quick_actions_on_empty_query()
    {
        $response = $this->actingAs($this->manager)
            ->getJson('/api/search');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'results' => [
                'quick_actions',
            ],
        ]);
    }

    public function test_api_search_finds_reservation_by_guest_name()
    {
        $response = $this->actingAs($this->manager)
            ->getJson('/api/search?q=Tanaka');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $data = $response->json('results');
        $this->assertNotEmpty($data['reservations']);
        $this->assertStringContainsString('Tanaka', $data['reservations'][0]['title']);
    }

    public function test_api_search_finds_room_by_number()
    {
        $response = $this->actingAs($this->manager)
            ->getJson('/api/search?q=501');

        $response->assertStatus(200);
        $data = $response->json('results');
        $this->assertNotEmpty($data['rooms']);
        $this->assertStringContainsString('Room 501', $data['rooms'][0]['title']);
    }

    public function test_api_search_finds_partner_by_name_or_company()
    {
        $response = $this->actingAs($this->manager)
            ->getJson('/api/search?q=Temairazu');

        $response->assertStatus(200);
        $data = $response->json('results');
        $this->assertNotEmpty($data['partners']);
        $this->assertStringContainsString('Temairazu', $data['partners'][0]['title']);
    }

    public function test_system_health_endpoint_returns_json()
    {
        $response = $this->actingAs($this->manager)
            ->getJson('/health?format=json');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'healthy',
            'platform' => 'ClearStay 3.0 Enterprise Engine',
        ]);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database',
                'outbox_pipeline',
                'cyber_defense',
                'environment',
            ],
        ]);
    }

    public function test_system_health_endpoint_renders_html_for_browser()
    {
        $response = $this->actingAs($this->manager)
            ->get('/health');

        $response->assertStatus(200);
        $response->assertViewIs('health.index');
        $response->assertSee('System Health &amp; Observability', false);
        $response->assertSee('ALL SYSTEMS OPERATIONAL');
        $response->assertSee('Database Subsystem');
    }

    public function test_pwa_manifest_and_sw_exist_in_public()
    {
        $manifestPath = public_path('manifest.json');
        $swPath = public_path('sw.js');

        $this->assertFileExists($manifestPath);
        $this->assertFileExists($swPath);

        $manifestContent = json_decode(file_get_contents($manifestPath), true);
        $this->assertEquals('ClearStay 3.0', $manifestContent['short_name']);
    }

    public function test_render_healthcheck_up_endpoint_returns_200_ok()
    {
        $response = $this->get('/up');
        $response->assertStatus(200);
    }
}
