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
        Schema::create('performance_review_references', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('group', [
                'Rispek',
                'Antusias',
                'Fatanah',
                'Amanah',
                'Aspek Tanggung Jawab',
                'Pendidikan',
                'Pengalaman Kerja',
            ]);
            $table->enum('type', [
                'Kompetensi Dasar',
                'Kompetensi Teknis',
            ]);
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_review_references');
    }
};
