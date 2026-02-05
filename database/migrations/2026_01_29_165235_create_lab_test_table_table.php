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
        Schema::create('lab_test_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('lab_test_documents')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('media_id')
                ->nullable()
                ->constrained('media')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->integer('page_number');
            $table->text('markdown')->nullable();
            $table->json('cells')->default('[]');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_test_tables');
    }
};
