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
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->date('project_date');
            $table->double('budget')->default(0);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
            $table->softDeletes('deleted_at');
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
