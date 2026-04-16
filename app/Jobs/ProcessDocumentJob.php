<?php

namespace App\Jobs;

use App\Actions\ClassifyLabTestResultAction;
use App\Actions\ExtractLabTestResults;
use App\Actions\ExtractLabTestTables;
use App\Models\LabTestDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected LabTestDocument $document
    ) {}

    public function handle(
        ExtractLabTestTables $extractTablesAction
    ): void {
        $tables = $extractTablesAction($this->document);

        foreach ($tables as $table) {
            ProcessDocumentTable::dispatch($table);
        }
    }
}
