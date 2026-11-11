<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan opsi kas baru ke enum rek_transfer pada tabel invoices.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `rek_transfer` ENUM(
            'BCA PT',
            'BCA BARU',
            'BCA LAMA',
            'MANDIRI',
            'KAS KECIL',
            'KAS BESAR',
            'BCA 425',
            'BCA 140',
            'BCA 436',
            'KAS BESAR KKP',
            'KAS KECIL KKP',
            'KAS KECIL PT',
            'KAS BESAR PT'
        ) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `invoices` MODIFY COLUMN `rek_transfer` ENUM(
            'BCA PT',
            'BCA BARU',
            'BCA LAMA',
            'MANDIRI',
            'KAS KECIL',
            'KAS BESAR',
            'BCA 425',
            'BCA 140',
            'BCA 436'
        ) NULL");
    }
};
