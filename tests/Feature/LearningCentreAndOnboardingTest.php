<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningCentreAndOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Hotel $hotel;

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
    }

    public function test_learning_centre_hub_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get('/learning-centre');

        $response->assertStatus(200);
        $response->assertSee('Learning Centre');
        $response->assertSee('Platform Overview');
        $response->assertSee('Reservation');
        $response->assertSee('Bulk Change');
        $response->assertSee('Synchronization');
        $response->assertSee('Analytics');
        $response->assertSee('Partner Assurance');
        $response->assertSee('Easy Mode');
        $response->assertSee('Responsive Experience');
    }

    public function test_video_streaming_endpoint(): void
    {
        $videoFilename = '01-clearstay-introduction.mp4';
        $response = $this->actingAs($this->user)->get("/videos/{$videoFilename}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'video/mp4');
    }

    public function test_onboarding_status_api(): void
    {
        RoomType::create([
            'hotel_id' => $this->hotel->id,
            'name' => 'Deluxe Suite',
            'code' => 'DLX-STE',
            'base_price' => 25000,
            'capacity' => 2,
            'total_rooms' => 5,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/onboarding/status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'completed_count',
            'total_count',
            'progress_percent',
            'is_dismissed',
            'steps' => [
                '*' => ['id', 'title', 'description', 'url', 'is_completed', 'icon'],
            ],
            'next_step' => [
                'id',
                'title',
                'description',
                'url',
                'is_completed',
                'icon',
            ],
        ]);

        $data = $response->json();
        $this->assertEquals(7, $data['total_count']);
        $this->assertGreaterThanOrEqual(2, $data['completed_count']); // hotel + room type
    }

    public function test_onboarding_dismiss_api(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/onboarding/dismiss');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertTrue(session('onboarding_dismissed'));
    }

    public function test_onboarding_reset_api(): void
    {
        session(['onboarding_dismissed' => true]);

        $response = $this->actingAs($this->user)->postJson('/api/onboarding/reset');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertFalse(session('onboarding_dismissed'));
    }
}
