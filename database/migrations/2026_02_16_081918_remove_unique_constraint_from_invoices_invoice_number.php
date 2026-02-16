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
        try {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique(['invoice_number']);
            });
        } catch (\Throwable $e) {
            // Index might not exist or have a different name. safe to ignore if it doesn't exist.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unique('invoice_number');
            });
        } catch (\Throwable $e) {
            // prevent duplicate key errors
        }
    }
};
