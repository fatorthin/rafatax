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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mou_id')->constrained('mous')->onDelete('cascade')->nullable();
            $table->foreignId('memo_id')->constrained('memos')->onDelete('cascade')->nullable();
            $table->string('invoice_number')->unique();
            $table->string('description')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->enum('invoice_status', ['unpaid', 'paid']);
            $table->enum('invoice_type', ['pt', 'kkp']);
            $table->boolean('is_saldo_awal')->default(false);
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
