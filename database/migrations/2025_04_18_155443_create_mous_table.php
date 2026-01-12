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
        Schema::create('mous', function (Blueprint $table) {
            $table->id();
            $table->string('mou_number')->unique()->required();
            $table->date('start_date')->required();
            $table->date('end_date')->required();
            $table->string('description');
            $table->enum('status', ['approved', 'unapproved']);
            $table->enum('type', ['pt', 'kkp']);
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('category_mou_id')->constrained('category_mous')->onDelete('cascade');
            $table->double('percentage_restitution')->default(0)->nullable();
            $table->text('link_mou')->nullable();
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mous');
    }
};
