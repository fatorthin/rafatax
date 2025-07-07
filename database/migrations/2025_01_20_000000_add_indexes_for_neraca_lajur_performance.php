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
        Schema::table('journal_book_reports', function (Blueprint $table) {
            $table->index(['transaction_date', 'deleted_at', 'journal_book_id', 'coa_id'], 'idx_jbr_date_deleted_book_coa');
            $table->index(['coa_id', 'transaction_date'], 'idx_jbr_coa_date');
        });

        Schema::table('cash_reports', function (Blueprint $table) {
            $table->index(['cash_reference_id', 'transaction_date', 'deleted_at', 'coa_id'], 'idx_cr_ref_date_deleted_coa');
            $table->index(['coa_id', 'transaction_date'], 'idx_cr_coa_date');
            $table->index(['transaction_date', 'cash_reference_id'], 'idx_cr_date_ref');
        });

        Schema::table('cash_references', function (Blueprint $table) {
            $table->index(['deleted_at'], 'idx_cref_deleted');
        });

        Schema::table('coa', function (Blueprint $table) {
            $table->index(['deleted_at', 'type'], 'idx_coa_deleted_type');
            $table->index(['code'], 'idx_coa_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_book_reports', function (Blueprint $table) {
            $table->dropIndex('idx_jbr_date_deleted_book_coa');
            $table->dropIndex('idx_jbr_coa_date');
        });

        Schema::table('cash_reports', function (Blueprint $table) {
            $table->dropIndex('idx_cr_ref_date_deleted_coa');
            $table->dropIndex('idx_cr_coa_date');
            $table->dropIndex('idx_cr_date_ref');
        });

        Schema::table('cash_references', function (Blueprint $table) {
            $table->dropIndex('idx_cref_deleted');
        });

        Schema::table('coa', function (Blueprint $table) {
            $table->dropIndex('idx_coa_deleted_type');
            $table->dropIndex('idx_coa_code');
        });
    }
}; 