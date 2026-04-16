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
        Schema::create('ai_model_pricings', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('model');
            $table->decimal('prompt_token_price', 12, 8)->default(0)->comment('Price per 1M tokens');
            $table->decimal('completion_token_price', 12, 8)->default(0)->comment('Price per 1M tokens');
            $table->decimal('thought_token_price', 12, 8)->default(0)->comment('Price per 1M tokens');
            $table->decimal('cache_read_token_price', 12, 8)->default(0)->comment('Price per 1M tokens');
            $table->decimal('cache_write_token_price', 12, 8)->default(0)->comment('Price per 1M tokens');
            $table->timestamps();

            $table->unique(['provider', 'model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_model_pricings');
    }
};
