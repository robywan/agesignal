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
            $table->text('loinc_justification')->nullable()->after('loinc_num');
            $table->decimal('loinc_confidence_score', 3, 2)->nullable()->after('loinc_justification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_test_results', function (Blueprint $table) {
            $table->dropColumn(['loinc_justification', 'loinc_confidence_score']);
        });
    }
};
