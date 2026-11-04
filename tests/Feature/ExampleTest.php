<?php

namespace Tests\Feature;

use App\Models\Hotel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        Hotel::create([
            'name' => 'ClearStay Ginza',
            'slug' => 'clearstay-ginza',
            'code' => 'GINZA',
            'address' => 'Ginza',
            'city' => 'Tokyo',
            'country' => 'JP',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

