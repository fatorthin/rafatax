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
        Schema::table('cash_reports', function (Blueprint $table) {
            $table->date('tanggal_bukti_potong_pph23')->nullable()->after('is_pph23_checked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_reports', function (Blueprint $table) {
            $table->dropColumn('tanggal_bukti_potong_pph23');
        });
    }
};
