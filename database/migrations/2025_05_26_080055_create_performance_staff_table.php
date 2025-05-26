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
        Schema::create('performance_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')
                ->constrained('staff')
                ->onDelete('cascade');
            $table->foreignId('performance_id')
                ->constrained('performance_review_references')
                ->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->double('self_assesment_score')
                ->default(0);
            $table->double('supervisor_assesment_score')
                ->default(0);
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_staff');
    }
};
