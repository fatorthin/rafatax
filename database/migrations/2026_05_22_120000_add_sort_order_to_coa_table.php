<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coa', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->nullable()->after('group_coa_id');
        });

        $order = 1;

        DB::table('coa')
            ->orderBy('id')
            ->select('id')
            ->chunkById(100, function ($rows) use (&$order): void {
                foreach ($rows as $row) {
                    DB::table('coa')
                        ->where('id', $row->id)
                        ->update(['sort_order' => $order++]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coa', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
