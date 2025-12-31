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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('company_name')->unique();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('owner_role')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('npwp')->nullable();
            $table->enum('jenis_wp', ['perseorangan', 'badan'])->default('perseorangan');
            $table->string('grade')->nullable();
            $table->boolean('pph_25_reporting')->default(false);
            $table->boolean('pph_23_reporting')->default(false);
            $table->boolean('pph_21_reporting')->default(false);
            $table->boolean('pph_4_reporting')->default(false);
            $table->boolean('ppn_reporting')->default(false);
            $table->boolean('spt_reporting')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('type', ['pt', 'kkp'])->default('pt');
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
