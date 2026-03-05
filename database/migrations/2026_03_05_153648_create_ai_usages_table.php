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
        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('provider');
            $table->string('model');
            $table->string('description')->nullable();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('thought_tokens')->nullable();
            $table->integer('cache_read_input_tokens')->nullable();
            $table->integer('cache_write_input_tokens')->nullable();
            $table->decimal('prompt_token_cost', 10, 4)->nullable();
            $table->decimal('completion_token_cost', 10, 4)->nullable();
            $table->decimal('thought_token_cost', 10, 4)->nullable();
            $table->decimal('cache_read_token_cost', 10, 4)->nullable();
            $table->decimal('cache_write_token_cost', 10, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usages');
    }
};
