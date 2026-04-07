<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE case_projects MODIFY COLUMN status ENUM('open', 'in_progress', 'done', 'paid') DEFAULT 'open'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE case_projects MODIFY COLUMN status ENUM('open', 'in_progress', 'done') DEFAULT 'open'");
    }
};
