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
        Schema::create('cash_reports', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->foreignId('cash_reference_id')->constrained('cash_references')->onDelete('cascade');
            $table->foreignId('mou_id')->constrained('mous')->onDelete('cascade')->nullable();
            $table->foreignId('coa_id')->constrained('coa')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade')->nullable();
            $table->foreignId('cost_list_invoice_id')->constrained('cost_list_invoices')->onDelete('cascade')->nullable()->change();
            $table->string('type')->nullable();
            $table->double('debit_amount');
            $table->double('credit_amount');
            $table->date('transaction_date');
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_reports');
    }
};
