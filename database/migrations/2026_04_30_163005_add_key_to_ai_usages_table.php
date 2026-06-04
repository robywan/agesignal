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
        Schema::table('ai_usages', function (Blueprint $table) {
            $table->string('key')->nullable()->after('id')->index();
            $table->renameColumn('thought_tokens', 'reasoning_tokens');
            $table->renameColumn('thought_token_cost', 'reasoning_token_cost');
            $table->renameColumn('description', 'agent');
            $table->dropColumn(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_usages', function (Blueprint $table) {
            $table->string('subject_type')->nullable()->after('id');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->renameColumn('agent', 'description');
            $table->renameColumn('reasoning_token_cost', 'thought_token_cost');
            $table->renameColumn('reasoning_tokens', 'thought_tokens');
            $table->dropColumn('key');
        });
    }
};
