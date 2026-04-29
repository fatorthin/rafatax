<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan nilai 'overdue' ke enum invoice_status.
     * MySQL mengharuskan ALTER TABLE untuk mengubah ENUM.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `invoice_status` ENUM('unpaid', 'paid', 'overdue') NOT NULL");
    }

    public function down(): void
    {
        // Kembalikan ke enum semula (data overdue akan ter-truncate jika ada)
        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `invoice_status` ENUM('unpaid', 'paid') NOT NULL");
    }
};
