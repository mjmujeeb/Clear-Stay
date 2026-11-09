<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservations') && !Schema::hasColumn('reservations', 'channel_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreignId('channel_id')->nullable()->after('room_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reservations') && Schema::hasColumn('reservations', 'channel_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropForeign(['channel_id']);
                $table->dropColumn('channel_id');
            });
        }
    }
};
