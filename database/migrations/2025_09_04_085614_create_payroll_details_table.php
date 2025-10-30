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
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls');
            $table->foreignId('staff_id')->constrained('staff');
            $table->double('salary');
            $table->double('bonus_position')->default(0);
            $table->double('bonus_competency')->default(0);
            $table->double('bonus_lain')->default(0);
            $table->double('overtime_count')->default(0);
            $table->double('visit_solo_count')->default(0);
            $table->double('visit_luar_solo_count')->default(0);
            $table->double('sick_leave_count')->default(0);
            $table->double('halfday_count')->default(0);
            $table->double('leave_count')->default(0);
            $table->double('cuti_count')->default(0);
            $table->double('cut_bpjs_kesehatan')->default(0);
            $table->double('cut_bpjs_ketenagakerjaan')->default(0);
            $table->double('cut_lain')->default(0);
            $table->double('cut_hutang')->default(0);
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
