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
        Schema::table('saldo_awal_piutangs', function (Blueprint $table) {
            $table->integer('year')->default(2024)->after('client_id');
            $table->string('notes')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saldo_awal_piutangs', function (Blueprint $table) {
            $table->dropColumn(['year', 'notes']);
        });
    }
};
