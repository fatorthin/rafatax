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
        Schema::table('clients', function (Blueprint $table) {
            $table->decimal('saldo_awal_2025_pt', 18, 2)->default(0)->after('type');
            $table->decimal('saldo_awal_2025_kkp', 18, 2)->default(0)->after('saldo_awal_2025_pt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['saldo_awal_2025_pt', 'saldo_awal_2025_kkp']);
        });
    }
};
