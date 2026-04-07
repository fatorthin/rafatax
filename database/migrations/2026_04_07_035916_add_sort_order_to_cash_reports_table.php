<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_reports', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('transaction_date');
        });

        // Initialize sort_order based on existing order (transaction_date, id)
        DB::statement('
            UPDATE cash_reports cr
            JOIN (
                SELECT id, ROW_NUMBER() OVER (
                    PARTITION BY cash_reference_id, YEAR(transaction_date), MONTH(transaction_date)
                    ORDER BY transaction_date ASC, id ASC
                ) as row_num
                FROM cash_reports
                WHERE deleted_at IS NULL
            ) ranked ON cr.id = ranked.id
            SET cr.sort_order = ranked.row_num
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_reports', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
