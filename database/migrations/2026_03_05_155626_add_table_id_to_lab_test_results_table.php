<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: add nullable table_id column
        Schema::table('lab_test_results', function (Blueprint $table) {
            $table->foreignId('table_id')
                ->nullable()
                ->after('id')
                ->constrained('lab_test_tables')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        // Step 2: populate table_id from the request join
        DB::table('lab_test_results')->eachById(function (object $row) {
            $tableId = DB::table('lab_test_result_requests')
                ->where('id', $row->request_id)
                ->value('table_id');

            DB::table('lab_test_results')
                ->where('id', $row->id)
                ->update(['table_id' => $tableId]);
        });

        // Step 3: make table_id not nullable
        Schema::table('lab_test_results', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable(false)->change();
        });

        // Step 4: drop request_id FK and column
        Schema::table('lab_test_results', function (Blueprint $table) {
            $table->dropForeign(['request_id']);
            $table->dropColumn('request_id');
        });
    }

    public function down(): void
    {
        Schema::table('lab_test_results', function (Blueprint $table) {
            $table->foreignId('request_id')
                ->nullable()
                ->after('id')
                ->constrained('lab_test_result_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        DB::table('lab_test_results')
            ->join('lab_test_result_requests', 'lab_test_result_requests.table_id', '=', 'lab_test_results.table_id')
            ->update(['lab_test_results.request_id' => DB::raw('lab_test_result_requests.id')]);

        Schema::table('lab_test_results', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropColumn('table_id');
        });
    }
};
