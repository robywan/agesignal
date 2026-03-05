<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class ImportLoincData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-loinc-data {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import LOINC data from a specified file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pathname = $this->argument('file');

        $csv = Reader::from($pathname, 'r')
            ->setHeaderOffset(0)
            ->setEscape('');

        foreach ($csv as $record) {
            DB::table('loinc_core_entries')->upsert([
                'loinc_num' => $record['LOINC_NUM'],
                'component' => $record['COMPONENT'],
                'property' => $record['PROPERTY'],
                'time_aspect' => $record['TIME_ASPCT'],
                'system' => $record['SYSTEM'],
                'scale_type' => $record['SCALE_TYP'],
                'method_type' => $record['METHOD_TYP'],
                'class' => $record['CLASS'],
                'class_type' => (int) $record['CLASSTYPE'],
                'long_common_name' => $record['LONG_COMMON_NAME'] ?: null,
                'short_name' => $record['SHORTNAME'] ?: null,
                'external_copyright_notice' => $record['EXTERNAL_COPYRIGHT_NOTICE'] ?: null,
                'status' => $record['STATUS'],
                'version_first_released' => $record['VersionFirstReleased'],
                'version_last_changed' => $record['VersionLastChanged'],
            ], ['loinc_num']);
        }
    }
}
