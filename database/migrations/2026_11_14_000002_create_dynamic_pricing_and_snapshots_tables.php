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
        // 1. Hotel-level pricing settings
        if (!Schema::hasTable('hotel_pricing_settings')) {
            Schema::create('hotel_pricing_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->string('stacking_policy')->default('additive'); // additive, multiplicative, highest_only
                $table->json('weekend_days')->nullable(); // e.g. [5, 6] for Friday and Saturday
                $table->decimal('weekend_increase_percentage', 5, 2)->default(15.00);
                $table->string('rounding_policy')->default('whole_yen'); // whole_yen, nearest_100, none
                $table->string('currency')->default('JPY');
                $table->decimal('tax_percentage', 5, 2)->default(10.00);
                $table->boolean('tax_included')->default(true);
                $table->timestamps();

                $table->unique('hotel_id');
            });
        }

        // 2. Extend calendar_events for hotel scoping, percentage adjustments, and active flag
        if (Schema::hasTable('calendar_events')) {
            Schema::table('calendar_events', function (Blueprint $table) {
                if (!Schema::hasColumn('calendar_events', 'hotel_id')) {
                    $table->foreignId('hotel_id')->nullable()->constrained()->nullOnDelete();
                }
                if (!Schema::hasColumn('calendar_events', 'percentage_adjustment')) {
                    $table->decimal('percentage_adjustment', 5, 2)->default(20.00);
                }
                if (!Schema::hasColumn('calendar_events', 'fixed_surcharge')) {
                    $table->decimal('fixed_surcharge', 10, 2)->default(0.00);
                }
                if (!Schema::hasColumn('calendar_events', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        // 3. Manager Configurable Pricing Rules (Seasonal, Events, Discounts)
        if (!Schema::hasTable('pricing_rules')) {
            Schema::create('pricing_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
                $table->string('rule_name');
                $table->string('rule_name_ja')->nullable();
                $table->string('rule_type')->default('seasonal'); // seasonal, special_event, national_holiday, custom_discount
                $table->decimal('percentage_adjustment', 5, 2)->default(0.00);
                $table->decimal('fixed_adjustment', 10, 2)->default(0.00);
                $table->date('start_date');
                $table->date('end_date');
                $table->json('weekdays')->nullable();
                $table->integer('priority')->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['hotel_id', 'start_date', 'end_date']);
            });
        }

        // 4. Immutable Reservation Pricing Snapshots
        if (!Schema::hasTable('reservation_pricing_snapshots')) {
            Schema::create('reservation_pricing_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
                $table->string('currency')->default('JPY');
                $table->decimal('base_total', 10, 2);
                $table->decimal('surcharge_total', 10, 2)->default(0.00);
                $table->decimal('discount_total', 10, 2)->default(0.00);
                $table->decimal('tax_total', 10, 2)->default(0.00);
                $table->decimal('grand_total', 10, 2);
                $table->string('pricing_rule_version')->nullable();
                $table->json('nightly_breakdown');
                $table->timestamp('calculated_at')->nullable();
                $table->timestamps();

                $table->index('reservation_id');
            });
        }

        // 5. Pricing Audit History
        if (!Schema::hasTable('pricing_audit_logs')) {
            Schema::create('pricing_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action'); // update_base_price, update_weekend_rate, update_holiday_rule, create_seasonal_rule, update_stacking_policy
                $table->string('target_type')->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('reason')->nullable();
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
        Schema::dropIfExists('pricing_audit_logs');
        Schema::dropIfExists('reservation_pricing_snapshots');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('hotel_pricing_settings');
    }
};
