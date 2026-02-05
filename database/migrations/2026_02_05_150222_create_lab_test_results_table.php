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
        Schema::create('lab_test_result_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')
                ->constrained('lab_test_tables')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('thought_tokens')->nullable();
            $table->integer('cache_read_input_tokens')->nullable();
            $table->integer('cache_write_input_tokens')->nullable();
            $table->timestamps();
        });

        Schema::create('lab_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')
                ->constrained('lab_test_result_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('value')->nullable();
            $table->string('unit_measure')->nullable();
            $table->string('reference_values')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_test_results');
        Schema::dropIfExists('lab_test_result_requests');
    }
};
