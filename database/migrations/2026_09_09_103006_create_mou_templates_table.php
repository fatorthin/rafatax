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
        Schema::create('mou_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('both'); // pt, kkp, both
            $table->text('description')->nullable();
            $table->json('sections')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mou_templates');
    }
};
