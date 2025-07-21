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
        Schema::create('daftar_aktiva_tetaps', function (Blueprint $table) {
            $table->id();
            $table->string('deskripsi');
            $table->date('tahun_perolehan');
            $table->integer('harga_perolehan');
            $table->integer('tarif_penyusutan');
            $table->timestamps();
            $table->softDeletes('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daftar_aktiva_tetaps');
    }
};
