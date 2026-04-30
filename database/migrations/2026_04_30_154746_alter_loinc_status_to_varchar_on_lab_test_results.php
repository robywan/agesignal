<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE lab_test_results MODIFY COLUMN loinc_status VARCHAR(32) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE lab_test_results MODIFY COLUMN loinc_status ENUM('mapped','unmapped','failed') NULL DEFAULT NULL");
    }
};
