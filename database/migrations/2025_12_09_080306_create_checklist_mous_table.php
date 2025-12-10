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
        Schema::create('checklist_mous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mou_id')->constrained('mous')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable();
            $table->date('checklist_date');
            $table->enum('status', ['pending', 'completed', 'overdue'])->default('pending');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_mous');
    }
};
