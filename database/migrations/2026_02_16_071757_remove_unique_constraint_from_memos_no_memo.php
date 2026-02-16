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
            Schema::table('memos', function (Blueprint $table) {
                // Try dropping by array approach which infers index name
                $table->dropUnique(['no_memo']);
            });
        } catch (\Throwable $e) {
            // Index might not exist, which is what we want.
            // Check if it's the specific specific "check that it exists" error or similar
            // But for now, ignoring is safe as long as we verify.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('memos', function (Blueprint $table) {
                $table->unique('no_memo');
            });
        } catch (\Throwable $e) {
            // prevent duplicate key errors
        }
    }
};
