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
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedInteger('rooms_count')->default(1)->after('guest_id');
            $table->string('group_booking_reference', 64)->nullable()->index()->after('rooms_count');
        });

        Schema::table('simulated_partner_reservations', function (Blueprint $table) {
            $table->unsignedInteger('rooms_count')->default(1)->after('guest_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['group_booking_reference', 'rooms_count']);
        });

        Schema::table('simulated_partner_reservations', function (Blueprint $table) {
            $table->dropColumn('rooms_count');
        });
    }
};
