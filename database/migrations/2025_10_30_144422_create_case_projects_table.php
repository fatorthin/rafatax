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
            $table->date('case_date');
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->text('link_dokumen')->nullable();
            $table->double('budget')->default(0);
            $table->string('external_id')->unique()->nullable()->comment('ID dari sistem KPI');
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
