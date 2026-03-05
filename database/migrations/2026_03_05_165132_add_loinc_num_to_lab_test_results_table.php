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
        Schema::table('lab_test_results', function (Blueprint $table) {
            $table->string('loinc_num', 20)
                ->nullable()
                ->after('notes')
                ->references('loinc_num')
                ->on('loinc_core_entries')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_test_results', function (Blueprint $table) {
            $table->dropColumn('loinc_num');
        });
    }
};
