<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('email');
            $table->string('sex', 16)->nullable()->after('birthdate');
            $table->unsignedSmallInteger('height_cm')->nullable()->after('sex');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('height_cm');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birthdate', 'sex', 'height_cm', 'weight_kg']);
        });
    }
};
