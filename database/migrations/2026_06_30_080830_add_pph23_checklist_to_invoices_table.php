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
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_pph23_checked')->default(false)->after('client_id');
            $table->date('tanggal_bukti_potong_pph23')->nullable()->after('is_pph23_checked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['is_pph23_checked', 'tanggal_bukti_potong_pph23']);
        });
    }
};
