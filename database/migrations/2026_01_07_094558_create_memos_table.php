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
        Schema::create('memos', function (Blueprint $table) {
            $table->id();
            $table->string('no_memo')->unique();
            $table->string('description')->nullable();
            $table->string('nama_klien');
            $table->string('instansi_klien');
            $table->text('alamat_klien')->nullable();
            $table->json('type_work');
            $table->date('tanggal_ttd');
            $table->enum('tipe_klien', ['pt', 'kkp'])->default('pt');
            $table->double('total_fee');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memos');
    }
};
