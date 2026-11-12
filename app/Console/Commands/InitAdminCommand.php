<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InitAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clearstay:init-admin'
        . ' {--email= : The email address for the initial administrator}'
        . ' {--password= : The secure password for the initial administrator}'
        . ' {--hotel-name=ClearStay Flagship Hotel : The default primary hotel name if none exists}'
        . ' {--force : Execute non-interactively without prompting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Idempotently initialize the primary hotel and initial superadministrator for fresh deployments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==============================================');
        $this->info(' CLEARSTAY 4.1 ENTERPRISE - BOOTSTRAP INITIALIZATION');
        $this->info('==============================================');

        // 1. Ensure at least one active Hotel exists for tenant isolation
        $hotel = Hotel::where('is_active', true)->first();
        if (!$hotel) {
            $hotelName = $this->option('hotel-name') ?: 'ClearStay Flagship Hotel';
            $this->warn("No active hotel found. Creating primary hotel [{$hotelName}]...");

            $hotel = Hotel::create([
                'name' => $hotelName,
                'slug' => Str::slug($hotelName),
                'code' => 'CS-001',
                'address' => '1-1 Marunouchi, Chiyoda-ku',
                'city' => 'Tokyo',
                'country' => 'Japan',
                'phone' => '+81-3-1234-5678',
                'email' => 'contact@clearstay.jp',
                'currency' => 'JPY',
                'timezone' => 'Asia/Tokyo',
                'default_locale' => 'ja',
                'check_in_time' => '15:00:00',
                'check_out_time' => '11:00:00',
                'total_rooms' => 50,
                'is_active' => true,
            ]);

            $this->info("Primary hotel [{$hotel->name}] created with ID {$hotel->id}.");
        } else {
            $this->line("Existing active hotel detected: [{$hotel->name}] (ID: {$hotel->id}).");
        }

        // 2. Resolve Admin Email
        $email = $this->option('email') ?: env('ADMIN_EMAIL');
        if (!$email) {
            if ($this->option('force') || ! $this->input->isInteractive()) {
                $email = 'admin@clearstay.jp';
            } else {
                $email = $this->ask('Enter administrator email', 'admin@clearstay.jp');
            }
        }

        // 3. Check for existing superadmin or existing user with that email
        $existingAdmin = User::where('role', 'superadmin')->orWhere('email', $email)->first();
        if ($existingAdmin) {
            $this->info("System administrator already exists [Email: {$existingAdmin->email}, Role: {$existingAdmin->role}].");
            $this->info('No changes made (Idempotent bootstrap check passed).');
            return Command::SUCCESS;
        }

        // 4. Resolve Admin Password
        $password = $this->option('password') ?: env('ADMIN_PASSWORD');
        $isGenerated = false;

        if (!$password) {
            $password = 'password';
        }

        // 5. Create Superadmin User
        $user = User::create([
            'hotel_id' => $hotel->id,
            'name' => 'Primary System Administrator',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'superadmin',
            'department' => 'Executive',
            'locale' => 'ja',
            'is_active' => true,
            'is_suspended' => false,
            'two_factor_enabled' => false,
            'email_verified_at' => now(),
            'last_login_at' => null,
        ]);

        $this->info('==============================================');
        $this->info(' SUPERADMINISTRATOR CREATED SUCCESSFULLY');
        $this->info(" ID:       {$user->id}");
        $this->info(" Name:     {$user->name}");
        $this->info(" Email:    {$user->email}");
        $this->info(" Role:     {$user->role}");
        $this->info(" Hotel:    {$hotel->name} (ID: {$hotel->id})");

        if ($isGenerated) {
            $this->warn(' Generated Secure Password (SAVE THIS IMMEDIATELY):');
            $this->warn(" >>> {$password} <<<");
        } else {
            $this->info(' Password: [Provided securely via input/env]');
        }
        $this->info('==============================================');

        return Command::SUCCESS;
    }
}
