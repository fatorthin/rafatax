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
        Schema::create('overtime_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')
                ->constrained('staff')
                ->onDelete('cascade');
            $table->string('overtime_date');
            $table->double('overtime_count')
                ->default(0);
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
        Schema::dropIfExists('overtime_counts');
    }
};
