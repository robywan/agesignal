<?php

namespace App\Jobs;

use App\Actions\ExtractLabTestResults;
use App\Models\LabTestTable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDocumentTable implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected LabTestTable $labTestTable
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        ExtractLabTestResults $extractResultsAction
    ): void {
        $results = $extractResultsAction($this->labTestTable);

        foreach ($results as $result) {
            ClassifyLabTestResultJob::dispatch($result);
        }
    }
}
