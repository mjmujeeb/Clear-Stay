<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoStreamingRangeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotel = Hotel::create([
            'name' => 'ClearStay Premier Tokyo Ginza',
            'slug' => 'clearstay-premier-tokyo-ginza',
            'code' => 'TYO-GNZ',
            'address' => '6-10-1 Ginza, Chuo-ku',
            'city' => 'Tokyo',
            'country' => 'JP',
            'total_rooms' => 50,
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Kenji Sato',
            'email' => 'kenji.sato@clearstay.test',
            'password' => bcrypt('Password123!'),
            'role' => 'manager',
            'hotel_id' => $this->hotel->id,
            'is_active' => true,
        ]);
    }

    public function test_learning_centre_renders_video_03_successfully()
    {
        $response = $this->actingAs($this->user)->get('/learning-centre?video=03');

        $response->assertStatus(200);
        $response->assertSee('03 — Manager Dashboard');
        $response->assertSee('03-inventory-bulk-update.webm');
        $response->assertSee('03-inventory-bulk-update.jpg');
    }

    public function test_video_endpoint_returns_200_without_range_header()
    {
        $response = $this->actingAs($this->user)->get('/videos/03-inventory-bulk-update.webm');

        $response->assertStatus(200);
        $this->assertEquals('video/webm', $response->headers->get('Content-Type'));
        $this->assertEquals('bytes', $response->headers->get('Accept-Ranges'));
        $this->assertGreaterThan(0, (int) $response->headers->get('Content-Length'));
    }

    public function test_video_endpoint_handles_rfc7233_range_requests_with_206_partial_content()
    {
        $response = $this->actingAs($this->user)->withHeaders([
            'Range' => 'bytes=0-1023',
        ])->get('/videos/03-inventory-bulk-update.webm');

        $response->assertStatus(206);
        $this->assertEquals('video/webm', $response->headers->get('Content-Type'));
        $this->assertEquals('bytes', $response->headers->get('Accept-Ranges'));
        $this->assertEquals('1024', $response->headers->get('Content-Length'));
        $this->assertStringStartsWith('bytes 0-1023/', $response->headers->get('Content-Range'));
    }

    public function test_caption_streaming_endpoint_returns_vtt()
    {
        $response = $this->actingAs($this->user)->get('/captions/03-inventory-bulk-update.vtt');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/vtt', $response->headers->get('Content-Type'));
    }

    public function test_poster_streaming_endpoint_returns_jpeg()
    {
        $response = $this->actingAs($this->user)->get('/posters/03-inventory-bulk-update.jpg');

        $response->assertStatus(200);
        $this->assertEquals('image/jpeg', $response->headers->get('Content-Type'));
    }
}
