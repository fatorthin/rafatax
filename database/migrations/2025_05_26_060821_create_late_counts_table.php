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
        Schema::create('late_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')
                ->constrained('staff')
                ->onDelete('cascade');
            $table->date('late_date')->required();
            $table->integer('late_count')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_counts');
    }
};
