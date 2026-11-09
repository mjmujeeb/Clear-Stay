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
        // 1. Immutable Traceable Inventory Ledger Table
        Schema::create('inventory_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('room_type_id')->nullable()->constrained('room_types')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->date('stay_date');
            $table->integer('previous_availability');
            $table->integer('new_availability');
            $table->integer('delta'); // +1 or -1 or custom delta
            $table->string('operation_type', 64); // BOOKING_CREATED, DATE_MODIFIED, CANCELLATION, OVERBOOKING_BUFFER, ROLLBACK
            $table->string('source', 64); // DIRECT_PMS, BOOKING_COM, AGODA, EXPEDIA, CONCURRENCY_LAB
            $table->string('actor_type', 32)->default('USER'); // USER, CHANNEL_WEBHOOK, SYSTEM, RMS_ENGINE
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('correlation_id', 64)->index();
            $table->string('idempotency_key', 128)->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['hotel_id', 'stay_date']);
            $table->index(['room_type_id', 'stay_date']);
        });

        // 2. Controlled Overbooking Policies Table
        Schema::create('overbooking_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->unique()->constrained('hotels')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->string('strategy', 32)->default('zero_overbooking'); // zero_overbooking, fixed_buffer, stochastic_optimization
            $table->integer('max_buffer_rooms')->default(2);
            $table->decimal('max_buffer_percentage', 5, 2)->default(5.00); // Max 5%
            $table->decimal('default_adr', 10, 2)->default(18500.00);
            $table->decimal('relocation_cost_per_walk', 10, 2)->default(35000.00);
            $table->decimal('historical_cancellation_rate', 5, 2)->default(14.50); // %
            $table->decimal('historical_noshow_rate', 5, 2)->default(4.20); // %
            $table->decimal('max_tolerated_walk_risk', 5, 2)->default(3.00); // %
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->text('policy_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overbooking_policies');
        Schema::dropIfExists('inventory_ledger_entries');
    }
};
