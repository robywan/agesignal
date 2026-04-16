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
            $table->decimal('numeric_value', 10, 3)->nullable();
            $table->string('operator', 2)->nullable(); // <, >
            $table->string('textual_value')->nullable();
            $table->boolean('is_abnormal')->default(false);
            $table->decimal('reference_min', 10, 3)->nullable();
            $table->decimal('reference_max', 10, 3)->nullable();
            $table->string('textual_range')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_test_results', function (Blueprint $table) {
            $table->dropColumn([
                'numeric_value',
                'operator',
                'textual_value',
                'is_abnormal',
                'reference_min',
                'reference_max',
                'textual_range',
            ]);
        });
    }
};
