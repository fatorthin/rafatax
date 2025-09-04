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
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade')->onUpdate('cascade');   
            $table->date('tanggal');
            $table->enum('status', ['masuk', 'sakit', 'izin', 'cuti', 'alfa', 'halfday']);
            $table->boolean('is_late')->default(false);
            $table->boolean('is_visit_solo')->default(false);
            $table->boolean('is_visit_luar_solo')->default(false);
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->double('durasi_lembur')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes('deleted_at', 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};
