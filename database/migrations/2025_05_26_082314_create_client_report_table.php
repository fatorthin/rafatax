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
        Schema::create('client_report', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('staff_id')
                ->constrained('staff')
                ->onDelete('cascade');
            $table->date('report_date');
            $table->enum('report_content', [
                'pph25',
                'pph21',
                'ppn',
            ]);
            $table->integer('score')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('staff')
                ->onDelete('set null');
            $table->dateTime('verified_at')->nullable();
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_report');
    }
};
