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
        // 1. Integration Mode Configuration Table
        Schema::create('integration_modes', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code', 64)->nullable()->unique(); // null = global default
            $table->enum('mode', ['simulator', 'sandbox', 'live'])->default('simulator');
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->dateTime('credentials_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Simulated Partner Remote Properties
        Schema::create('simulated_partner_properties', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code', 64); // booking_com, agoda, expedia, rakuten, jalan, direct_web, sakura_pms
            $table->string('property_code', 64); // e.g. BKG-TYO-001
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('currency', 3)->default('JPY');
            $table->string('timezone', 64)->default('Asia/Tokyo');
            $table->boolean('is_connected')->default(true);
            $table->string('api_version', 32)->default('v2.4');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['partner_code', 'hotel_id']);
            $table->unique(['partner_code', 'property_code']);
        });

        // 3. Simulated Partner Room Mappings
        Schema::create('simulated_partner_room_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code', 64);
            $table->foreignId('property_id')->constrained('simulated_partner_properties')->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('remote_room_code', 64); // e.g. BKG_RM_DLX_KING
            $table->string('remote_room_name', 128);
            $table->string('remote_rate_plan_code', 64)->default('RATE_STANDARD');
            $table->integer('max_occupancy')->default(2);
            $table->decimal('base_rate', 10, 2)->default(20000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'remote_room_code']);
            $table->index(['partner_code', 'room_type_id']);
        });

        // 4. Independent Remote Partner Inventory
        Schema::create('simulated_partner_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code', 64);
            $table->foreignId('property_id')->constrained('simulated_partner_properties')->cascadeOnDelete();
            $table->string('remote_room_code', 64);
            $table->string('date', 10); // Strict Y-m-d format
            $table->integer('available_units')->default(0);
            $table->decimal('base_rate', 10, 2)->default(20000);
            $table->boolean('stop_sell')->default(false);
            $table->integer('min_stay')->default(1);
            $table->boolean('closed_to_arrival')->default(false);
            $table->boolean('closed_to_departure')->default(false);
            $table->integer('version')->default(1);
            $table->dateTime('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['partner_code', 'property_id', 'remote_room_code', 'date'], 'sim_inventory_unique_key');
            $table->index(['partner_code', 'date']);
        });

        // 5. Simulated Partner Reservations (Inbound OTA Bookings)
        Schema::create('simulated_partner_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code', 64);
            $table->string('external_reservation_id', 64);
            $table->string('property_code', 64);
            $table->string('remote_room_code', 64);
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone')->nullable();
            $table->string('check_in_date', 10);
            $table->string('check_out_date', 10);
            $table->integer('guest_count')->default(2);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('JPY');
            $table->enum('status', ['confirmed', 'modified', 'cancelled'])->default('confirmed');
            $table->string('idempotency_key', 128)->nullable();
            $table->json('raw_payload')->nullable();
            $table->foreignId('clearstay_reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->timestamps();

            $table->unique(['partner_code', 'external_reservation_id']);
            $table->index(['partner_code', 'idempotency_key']);
        });

        // 6. Failure Injection Configuration Lab
        Schema::create('simulated_failure_configs', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code', 64); // specific partner or 'all'
            $table->string('operation', 64)->default('all'); // inventory_push, rate_push, booking_inbound, connection_test, all
            $table->string('failure_type', 64)->default('none'); // none, 400_bad_request, 401_unauthorized, 403_forbidden, 409_conflict, 429_rate_limit, 500_server_error, 504_timeout, latency_delay, malformed_json, invalid_date, missing_mapping, invalid_rate_plan
            $table->integer('http_status')->default(200);
            $table->integer('latency_ms')->default(0);
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('is_active')->default(false);
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['partner_code', 'operation']);
        });

        // 7. Simulated Partner Request & Response Audit Log
        Schema::create('simulated_partner_requests', function (Blueprint $table) {
            $table->id();
            $table->string('correlation_id', 64)->index();
            $table->string('partner_code', 64)->index();
            $table->enum('direction', ['outbound_push', 'inbound_webhook'])->default('outbound_push');
            $table->string('endpoint');
            $table->integer('http_status')->default(200);
            $table->integer('duration_ms')->default(0);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->enum('status', ['success', 'failure'])->default('success');
            $table->text('error_summary')->nullable();
            $table->timestamps();

            $table->index(['partner_code', 'direction', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulated_partner_requests');
        Schema::dropIfExists('simulated_failure_configs');
        Schema::dropIfExists('simulated_partner_reservations');
        Schema::dropIfExists('simulated_partner_inventory');
        Schema::dropIfExists('simulated_partner_room_mappings');
        Schema::dropIfExists('simulated_partner_properties');
        Schema::dropIfExists('integration_modes');
    }
};
