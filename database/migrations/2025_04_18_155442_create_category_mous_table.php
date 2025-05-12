<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CategoryMou;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_mous', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes('deleted_at', precision: 0);
        });

        CategoryMou::create([
            'name' => 'SPT',
        ], [
            'name' => 'Bulanan',
        ], [
            'name' => 'SP2DK',
        ], [
            'name' => 'Pembetulan',
        ], [
            'name' => 'Pemerikasaan',
        ], [
            'name' => 'Restitusi',
        ], [
            'name' => 'Keberatan',
        ], [
            'name' => 'Konsultasi',
        ], [
            'name' => 'Pembukuan',
        ], [
            'name' => 'Pelatihan',
        ], [
            'name' => 'Lainnya',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_mous');
    }
};
