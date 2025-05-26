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
        Schema::create('training_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')
                ->constrained('trainings')
                ->onDelete('cascade');
            $table->foreignId('staff_id')
                ->constrained('staff')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_staff');
    }
};
