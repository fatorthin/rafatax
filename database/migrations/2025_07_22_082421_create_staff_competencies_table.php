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
        Schema::create('staff_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff');    
            $table->string('competency');
            $table->date('date_of_assessment');
            $table->date('date_of_expiry');
            $table->timestamps();
            $table->softDeletes('deleted_at', 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_competencies');
    }
};
