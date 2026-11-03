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
        \Illuminate\Support\Facades\DB::table('invoices')
            ->where('invoice_status', 'paid')
            ->where(function ($query) {
                $query->where('is_send_invoice', false)
                    ->orWhere('is_send_invoice', 0)
                    ->orWhereNull('is_send_invoice');
            })
            ->update([
                'is_send_invoice' => 1,
                'send_invoice_date' => \Illuminate\Support\Facades\DB::raw("COALESCE(send_invoice_date, tgl_transfer, invoice_date, CURRENT_DATE)"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as this is a data synchronization migration
    }
};
