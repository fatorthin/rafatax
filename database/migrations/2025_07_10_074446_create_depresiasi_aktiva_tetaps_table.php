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
        Schema::create('depresiasi_aktiva_tetaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daftar_aktiva_tetap_id')->constrained('daftar_aktiva_tetaps')->onDelete('cascade');
            $table->date('tanggal_penyusutan');
            $table->double('jumlah_penyusutan');
            $table->timestamps();
            $table->softDeletes('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depresiasi_aktiva_tetaps');
    }
};
