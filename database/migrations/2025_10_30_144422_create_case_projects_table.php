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
        Schema::create('case_projects', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            // $table->date('case_date')->nullable();
            $table->enum('status', ['open', 'in_progress', 'done'])->default('open');
            $table->enum('case_type', ['SP2DK', 'Pembetulan', 'Pemeriksaan', 'Himbauan', 'Lainnya'])->nullable();
            $table->json('staff_id')->nullable();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('mou_id')->constrained()->onDelete('cascade')->nullable();
            $table->string('case_letter_number')->nullable();
            $table->date('case_letter_date')->nullable();
            $table->string('power_of_attorney_number')->nullable();
            $table->date('power_of_attorney_date')->nullable();
            $table->date('filling_drive')->nullable();
            $table->date('report_date')->nullable();
            $table->date('share_client_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_projects');
    }
};
