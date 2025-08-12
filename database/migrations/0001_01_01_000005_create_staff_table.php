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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('birth_place')->nullable(); // Added birth_place field
            $table->date('birth_date')->nullable(); // Added birth_date field
            $table->text('address')->nullable(); // Added address field
            $table->string('no_ktp')->nullable(); // Added no_ktp field
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('no_spk')->nullable(); // Added no_spk field
            $table->enum('jenjang', ['SMA', 'D-3', 'D-4', 'S-1', 'S-2', 'S-3']); // Added janjang field
            $table->string('jurusan')->nullable(); // Added jurusan field
            $table->string('university')->nullable(); // Added university field
            $table->string('no_ijazah')->nullable(); // Added no_ijazah field
            $table->date('tmt_training')->nullable(); // Added tmt_training field
            $table->string('periode')->nullable(); // Added periode field
            $table->string('selesai_training')->nullable(); // Added selesai_training field
            $table->foreignId('department_reference_id')
                ->nullable()
                ->constrained('department_references')
                ->onDelete('set null'); // Foreign key to department_references table
            $table->foreignId('position_reference_id')
                ->nullable()
                ->constrained('position_references')
                ->onDelete('set null'); // Foreign key to position_references table
            $table->enum('position_status', ['tetap', 'plt/kontrak', 'magang'])->default('tetap'); // Added position_status field
            $table->boolean('is_active')->default(true); // Added is_active field
            $table->double('salary')->nullable(); // Added salary field
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
