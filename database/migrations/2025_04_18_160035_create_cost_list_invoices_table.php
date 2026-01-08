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
        Schema::create('cost_list_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mou_id')->constrained('mous')->onDelete('cascade')->nullable();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('coa_id')->constrained('coa')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->double('amount');
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_list_invoices');
    }
};
