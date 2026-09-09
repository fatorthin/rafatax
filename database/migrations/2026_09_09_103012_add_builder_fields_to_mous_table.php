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
        Schema::table('mous', function (Blueprint $table) {
            $table->foreignId('mou_template_id')->nullable()->after('category_mou_id')->constrained('mou_templates')->nullOnDelete();
            $table->boolean('has_custom_builder')->default(false)->after('mou_template_id');
            $table->json('custom_sections')->nullable()->after('has_custom_builder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mous', function (Blueprint $table) {
            $table->dropForeign(['mou_template_id']);
            $table->dropColumn(['mou_template_id', 'has_custom_builder', 'custom_sections']);
        });
    }
};
