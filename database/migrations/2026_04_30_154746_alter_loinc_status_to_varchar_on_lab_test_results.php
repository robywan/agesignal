<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_test_results', function (Blueprint $table): void {
            $table->string('loinc_status', 32)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lab_test_results', function (Blueprint $table): void {
            $table->string('loinc_status', 32)->nullable()->change();
        });
    }
};
