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
        Schema::table('case_projects', function (Blueprint $table) {
            $table->foreignId('memo_id')->nullable()->after('mou_id')->constrained('memos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('case_projects', function (Blueprint $table) {
            $table->dropForeign(['memo_id']);
            $table->dropColumn('memo_id');
        });
    }
};
