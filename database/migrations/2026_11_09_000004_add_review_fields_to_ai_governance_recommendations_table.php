<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_governance_recommendations')) {
            Schema::table('ai_governance_recommendations', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_governance_recommendations', 'reviewed_by')) {
                    $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('ai_governance_recommendations', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
                if (!Schema::hasColumn('ai_governance_recommendations', 'review_notes')) {
                    $table->text('review_notes')->nullable()->after('reviewed_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_governance_recommendations')) {
            Schema::table('ai_governance_recommendations', function (Blueprint $table) {
                if (Schema::hasColumn('ai_governance_recommendations', 'reviewed_by')) {
                    $table->dropForeign(['reviewed_by']);
                    $table->dropColumn(['reviewed_by', 'reviewed_at', 'review_notes']);
                }
            });
        }
    }
};
