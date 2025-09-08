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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Format: resource.action (e.g., 'user.view', 'invoice.create')
            $table->string('display_name'); // Nama yang ditampilkan (e.g., 'View Users', 'Create Invoice')
            $table->text('description')->nullable(); // Deskripsi permission
            $table->string('resource'); // Nama resource (e.g., 'user', 'invoice', 'staff')
            $table->string('action'); // Action (e.g., 'view', 'create', 'edit', 'delete')
            $table->timestamps();
            
            // Index untuk performa
            $table->index(['resource', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
