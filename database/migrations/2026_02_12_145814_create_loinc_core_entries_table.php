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
        Schema::create('loinc_core_entries', function (Blueprint $table) {
            $table->string('loinc_num', 20)
                ->unique()
                ->primary();
            $table->string('component')->index();
            $table->string('property')->index();
            $table->string('time_aspect', 40);
            $table->string('system')->index();
            $table->string('scale_type')->index();
            $table->string('method_type')->nullable();
            $table->string('class');
            $table->integer('class_type');
            $table->text('long_common_name')->nullable()->index();
            $table->string('short_name')->nullable();
            $table->text('external_copyright_notice')->nullable();
            $table->string('status');
            $table->string('version_first_released');
            $table->string('version_last_changed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loinc_core_entries');
    }
};
