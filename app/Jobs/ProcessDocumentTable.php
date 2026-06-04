<?php

namespace App\Jobs;

use App\Actions\ExtractLabTestResults;
use App\Enums\LabTestResultRequestStatus;
use App\Models\LabTestTable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

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
        $this->labTestTable->fill([
            'request_status' => LabTestResultRequestStatus::Processing,
        ])->save();

        $results = $extractResultsAction($this->labTestTable);

        $this->labTestTable->document->syncStatusFromTables();

        foreach ($results as $result) {
            ClassifyLabTestResultJob::dispatch($result);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->labTestTable->fill([
            'request_status' => LabTestResultRequestStatus::Failed,
        ])->save();

        $this->labTestTable->document->syncStatusFromTables();
    }
}
