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
        // 1. Hotels Table
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code', 10)->unique();
            $table->string('address');
            $table->string('city');
            $table->string('country', 10)->default('JP');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('currency', 5)->default('JPY');
            $table->string('timezone', 50)->default('Asia/Tokyo');
            $table->string('default_locale', 5)->default('ja');
            $table->time('check_in_time')->default('15:00:00');
            $table->time('check_out_time')->default('11:00:00');
            $table->integer('total_rooms')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Add Hotel and Role columns to Users Table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete()->after('id');
            $table->string('role', 30)->default('frontdesk')->after('email'); // superadmin, manager, frontdesk, housekeeping
            $table->string('phone')->nullable()->after('role');
            $table->string('locale', 5)->default('en')->after('phone'); // en, ja
            $table->boolean('is_active')->default(true)->after('locale');
        });

        // 3. Room Types Table
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->integer('capacity')->default(2);
            $table->decimal('base_price', 10, 2);
            $table->text('description')->nullable();
            $table->json('amenities')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'code']);
        });

        // 4. Rooms Table
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->string('room_number', 20);
            $table->integer('floor')->default(1);
            $table->string('status', 20)->default('clean'); // clean, dirty, inspecting, maintenance
            $table->boolean('is_occupied')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['hotel_id', 'room_number']);
            $table->index(['hotel_id', 'status']);
        });

        // 5. Guests Table
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('passport_or_id', 50)->nullable();
            $table->string('vip_level', 20)->default('standard'); // standard, silver, gold, platinum
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'email']);
            $table->index(['hotel_id', 'last_name']);
        });

        // 6. Reservations Table
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('confirmation_code', 30)->unique();
            $table->string('guest_portal_token', 64)->unique();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->decimal('total_price', 10, 2);
            $table->string('payment_status', 20)->default('unpaid'); // paid, partially_paid, unpaid, refunded
            $table->string('status', 20)->default('confirmed'); // confirmed, checked_in, checked_out, cancelled
            $table->text('special_requests')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('source', 30)->default('direct'); // direct, ota, phone, walk_in, csv_import
            $table->timestamps();

            $table->index(['hotel_id', 'status']);
            $table->index(['room_id', 'check_in_date', 'check_out_date']);
            $table->index(['hotel_id', 'check_in_date']);
            $table->index(['hotel_id', 'check_out_date']);
        });

        // 7. Reservation Changes / Date Changes Table (Audit for transactional lock updates)
        Schema::create('reservation_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('old_check_in_date');
            $table->date('old_check_out_date');
            $table->date('new_check_in_date');
            $table->date('new_check_out_date');
            $table->foreignId('old_room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('new_room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->decimal('old_total_price', 10, 2)->nullable();
            $table->decimal('new_total_price', 10, 2)->nullable();
            $table->string('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'created_at']);
        });

        // 8. Housekeeping Tasks Table
        Schema::create('housekeeping_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('task_type', 30)->default('daily_cleaning'); // checkout_turnover, daily_cleaning, deep_clean, inspection, maintenance
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->string('status', 20)->default('pending'); // pending, in_progress, completed, verified
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'status']);
            $table->index(['room_id', 'status']);
        });

        // 9. Audit Logs Table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->nullable()->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50); // reservation.date_change, room.status_change, csv.import, etc.
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'action']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('housekeeping_tasks');
        Schema::dropIfExists('reservation_changes');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('guests');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['hotel_id']);
            $table->dropColumn(['hotel_id', 'role', 'phone', 'locale', 'is_active']);
        });

        Schema::dropIfExists('hotels');
    }
};
