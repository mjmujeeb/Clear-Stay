<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreshDatabaseTest extends TestCase
{
    use RefreshDatabase;

    // Notice: We do NOT seed any hotel or user. The database has tables but 0 rows!

    public function test_homepage_loads_safely_on_completely_empty_database(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_login_page_loads_safely_on_completely_empty_database(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_register_page_loads_safely_on_completely_empty_database(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_rooms_page_loads_safely_on_completely_empty_database(): void
    {
        $response = $this->get('/rooms');
        $response->assertStatus(200);
    }

    public function test_features_page_loads_safely_on_completely_empty_database(): void
    {
        $response = $this->get('/features');
        $response->assertStatus(200);
    }

    public function test_contact_page_loads_safely_on_completely_empty_database(): void
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
    }

    public function test_unauthenticated_dashboard_redirects_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_health_check_up_returns_200(): void
    {
        $response = $this->get('/up');
        $response->assertStatus(200);
    }
}
