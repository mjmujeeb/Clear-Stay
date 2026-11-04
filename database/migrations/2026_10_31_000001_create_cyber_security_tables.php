<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add 2FA & Security Tracking Columns to Users Table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('two_factor_enabled')->default(false)->after('password');
            $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('last_login_at')->nullable()->after('two_factor_recovery_codes');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->integer('failed_login_attempts')->default(0)->after('last_login_ip');
            $table->timestamp('lockout_until')->nullable()->after('failed_login_attempts');
        });

        // 2. Blocked IPs Table (IP Firewall & Threat Mitigation)
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason');
            $table->string('threat_level', 20)->default('high'); // medium, high, critical
            $table->integer('violation_count')->default(1);
            $table->timestamp('expires_at')->nullable(); // null = permanent
            $table->timestamps();

            $table->index('ip_address');
            $table->index('expires_at');
        });

        // 3. Security Incidents Table (Cyber Threat Intelligence Log)
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 60); // bot.honeypot_triggered, bot.rapid_submission, bot.challenge_failed, bot.scanner_blocked, auth.brute_force, etc.
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->string('ip_address', 45);
            $table->string('request_method', 10)->nullable();
            $table->text('endpoint')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();

            $table->index('event_type');
            $table->index('severity');
            $table->index('ip_address');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
        Schema::dropIfExists('blocked_ips');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'last_login_at',
                'last_login_ip',
                'failed_login_attempts',
                'lockout_until',
            ]);
        });
    }
};
