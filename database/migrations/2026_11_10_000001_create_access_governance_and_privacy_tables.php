<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for ClearStay 4.0 Access Governance and Privacy Architecture.
     */
    public function up(): void
    {
        // 1. Permission Elevation Requests (Two-Person Approval Workflow)
        if (!Schema::hasTable('permission_requests')) {
            Schema::create('permission_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->nullable()->constrained('hotels')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('risk_level')->default('medium'); // low, medium, high, critical
                $table->string('status')->default('pending'); // pending, approved, rejected, expired, revoked
                $table->text('justification');
                $table->integer('duration_hours')->default(24);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();

                $table->index(['hotel_id', 'status']);
                $table->index(['user_id', 'status']);
            });
        }

        // 2. Privacy & Sensitive Data Access Audit Logs (DPDP Act 2023 / Rules 2025 Architecture)
        if (!Schema::hasTable('privacy_access_logs')) {
            Schema::create('privacy_access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('guest_id')->nullable()->constrained('guests')->nullOnDelete();
                $table->string('action'); // unmask, export, view, anonymize
                $table->string('field_accessed')->default('phone_and_email');
                $table->text('justification');
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['hotel_id', 'created_at']);
            });
        }

        // 3. User Accessibility and Contrast Theme Preferences
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'contrast_theme')) {
                $table->string('contrast_theme')->default('standard')->after('locale');
            }
            if (!Schema::hasColumn('users', 'font_scale')) {
                $table->integer('font_scale')->default(100)->after('contrast_theme');
            }
            if (!Schema::hasColumn('users', 'reduced_motion')) {
                $table->boolean('reduced_motion')->default(false)->after('font_scale');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privacy_access_logs');
        Schema::dropIfExists('permission_requests');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'reduced_motion')) {
                $table->dropColumn('reduced_motion');
            }
            if (Schema::hasColumn('users', 'font_scale')) {
                $table->dropColumn('font_scale');
            }
            if (Schema::hasColumn('users', 'contrast_theme')) {
                $table->dropColumn('contrast_theme');
            }
        });
    }
};
