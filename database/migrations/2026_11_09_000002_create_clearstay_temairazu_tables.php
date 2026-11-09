<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Temairazu-oriented enterprise enhancements:
     * 1. Booking Snapshots & Historical Pace (BA+ Direction)
     * 2. Payment Reconciliations (Agoda / NTT DATA Collaboration Workflow)
     * 3. Central Inventory Reconciliations (Channel vs PMS Discrepancy Auditing)
     */
    public function up(): void
    {
        // 1. Booking Snapshots (TEMAIRAZU JIDO BA+ Style Historical Analytics)
        if (!Schema::hasTable('booking_snapshots')) {
            Schema::create('booking_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->date('snapshot_date'); // Date snapshot was captured (e.g. 7 days ago, yesterday, today)
                $table->date('stay_date');     // Target arrival/stay date
                $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('channel_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('booking_count')->default(0);
                $table->integer('occupied_rooms')->default(0);
                $table->integer('available_rooms')->default(0);
                $table->decimal('revenue', 12, 2)->default(0.00);
                $table->integer('cancellation_count')->default(0);
                $table->decimal('adr', 10, 2)->default(0.00);
                $table->decimal('revpar', 10, 2)->default(0.00);
                $table->decimal('occupancy_rate', 5, 2)->default(0.00);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['hotel_id', 'snapshot_date', 'stay_date']);
                $table->index(['hotel_id', 'stay_date']);
            });
        }

        // 2. Reservation-to-Payment Reconciliations (Agoda & NTT DATA Settlement Workflow)
        if (!Schema::hasTable('payment_reconciliations')) {
            Schema::create('payment_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
                $table->string('reservation_reference')->index();
                $table->string('payment_reference')->index();
                $table->foreignId('channel_id')->nullable()->constrained()->nullOnDelete();
                $table->string('payment_gateway')->default('Agoda Pay / NTT DATA Virtual Card');
                $table->string('payment_method')->default('virtual_credit_card'); // virtual_credit_card, bank_transfer, direct_cc
                $table->decimal('expected_amount', 12, 2)->default(0.00);
                $table->decimal('received_amount', 12, 2)->default(0.00);
                $table->decimal('discrepancy_amount', 12, 2)->default(0.00);
                $table->string('currency', 3)->default('JPY');
                $table->string('settlement_status')->default('settled'); // settled, pending_settlement, disputed, refunded, failed
                $table->string('reconciliation_status')->default('matched'); // matched, unmatched, partial, pending, failed
                $table->date('transaction_date');
                $table->timestamp('settled_at')->nullable();
                $table->text('exception_reason')->nullable();
                $table->json('audit_history')->nullable();
                $table->timestamps();

                $table->index(['hotel_id', 'reconciliation_status', 'transaction_date']);
            });
        }

        // 3. Central Inventory Reconciliations (Audit Discrepancies between PMS & External OTAs)
        if (!Schema::hasTable('inventory_reconciliations')) {
            Schema::create('inventory_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
                $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
                $table->date('stay_date');
                $table->integer('central_units')->default(0);
                $table->integer('channel_units')->default(0);
                $table->integer('difference')->default(0);
                $table->string('status')->default('in_sync'); // in_sync, discrepancy, resolved
                $table->timestamp('last_synced_at')->nullable();
                $table->string('suggested_action')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->unique(['hotel_id', 'room_type_id', 'channel_id', 'stay_date'], 'inv_recon_unique');
                $table->index(['hotel_id', 'status', 'stay_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reconciliations');
        Schema::dropIfExists('payment_reconciliations');
        Schema::dropIfExists('booking_snapshots');
    }
};
