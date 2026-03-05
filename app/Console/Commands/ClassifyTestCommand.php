<?php

namespace App\Console\Commands;

use App\Actions\ClassifyLabTestAction;
use App\Models\LabTestResult;
use App\Models\LoincCoreEntry;
use Illuminate\Console\Command;

class ClassifyTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:classify-test {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Classify test data.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $labTestResult = LabTestResult::query()
            ->findOrFail($this->argument('id'));

        $this->info($labTestResult->toJson(JSON_PRETTY_PRINT));

        $response = app(ClassifyLabTestAction::class)($labTestResult);

        $this->info(json_encode($response->usage, JSON_PRETTY_PRINT));
        $this->info(json_encode($response->structured, JSON_PRETTY_PRINT));

        $loincEntry = LoincCoreEntry::query()
            ->where('loinc_num', $response->structured[0]['loinc_code'])
            ->first();

        if ($loincEntry) {
            $this->info($loincEntry->toJson(JSON_PRETTY_PRINT));
        } else {
            $this->warn('LOINC entry not found.');
        }
    }
}
