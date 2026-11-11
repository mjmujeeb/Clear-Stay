<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_init_admin_creates_hotel_and_superadmin_when_empty(): void
    {
        $this->assertEquals(0, Hotel::count());
        $this->assertEquals(0, User::count());

        $this->artisan('clearstay:init-admin', [
            '--email' => 'admin@clearstay.jp',
            '--password' => 'SecurePass123!#',
            '--hotel-name' => 'Tokyo Tower Hotel',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertEquals(1, Hotel::count());
        $this->assertEquals(1, User::count());

        $hotel = Hotel::first();
        $this->assertEquals('Tokyo Tower Hotel', $hotel->name);
        $this->assertTrue($hotel->is_active);

        $admin = User::first();
        $this->assertEquals('admin@clearstay.jp', $admin->email);
        $this->assertEquals('superadmin', $admin->role);
        $this->assertEquals($hotel->id, $admin->hotel_id);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('SecurePass123!#', $admin->password));
    }

    public function test_init_admin_is_idempotent_and_does_not_duplicate_admin(): void
    {
        // First run
        $this->artisan('clearstay:init-admin', [
            '--email' => 'admin@clearstay.jp',
            '--password' => 'SecurePass123!#',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertEquals(1, User::count());

        // Second run with different password
        $this->artisan('clearstay:init-admin', [
            '--email' => 'admin@clearstay.jp',
            '--password' => 'DifferentPassword999',
            '--force' => true,
        ])->assertSuccessful();

        // Count must still be 1, original password preserved
        $this->assertEquals(1, User::count());
        $admin = User::first();
        $this->assertTrue(Hash::check('SecurePass123!#', $admin->password));
    }
}
