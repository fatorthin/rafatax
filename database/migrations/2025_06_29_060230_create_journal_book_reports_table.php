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
        Schema::create('journal_book_reports', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->foreignId('journal_book_id')->constrained('journal_book_references')->onDelete('cascade');
            $table->double('debit_amount')->default(0);
            $table->double('credit_amount')->default(0);
            $table->foreignId('coa_id')->constrained('coa')->onDelete('cascade');
            $table->date('transaction_date');
            $table->softDeletes('deleted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_book_reports');
    }
};
