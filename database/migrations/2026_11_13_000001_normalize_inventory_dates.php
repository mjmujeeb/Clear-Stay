<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Non-destructively normalizes all stored dates in central_inventories and inventory_reconciliations to YYYY-MM-DD.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("UPDATE central_inventories SET date = substr(date, 1, 10) WHERE length(date) > 10");
            DB::statement("UPDATE inventory_reconciliations SET stay_date = substr(stay_date, 1, 10) WHERE length(stay_date) > 10");
        } else {
            // For PostgreSQL / MySQL
            DB::statement("UPDATE central_inventories SET date = CAST(date AS DATE) WHERE date IS NOT NULL");
            DB::statement("UPDATE inventory_reconciliations SET stay_date = CAST(stay_date AS DATE) WHERE stay_date IS NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive, nothing to reverse
    }
};
