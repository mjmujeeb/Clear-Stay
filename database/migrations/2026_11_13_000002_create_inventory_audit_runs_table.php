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
        if (!Schema::hasTable('inventory_audit_runs')) {
            Schema::create('inventory_audit_runs', function (Blueprint $table) {
                $table->id();
                $table->string('audit_uuid', 64)->unique();
                $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
                $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->integer('duration_ms')->default(0);
                $table->json('selected_channels')->nullable();
                $table->integer('total_pairs_checked')->default(0);
                $table->integer('matching_pairs')->default(0);
                $table->integer('discrepancies')->default(0);
                $table->integer('critical_risks')->default(0);
                $table->integer('failed_checks')->default(0);
                $table->integer('warnings_count')->default(0);
                $table->string('final_status', 32)->default('completed'); // completed, discrepancies_detected, critical_risk, failed
                $table->json('summary_notes')->nullable();
                $table->timestamps();

                $table->index(['hotel_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_audit_runs');
    }
};
