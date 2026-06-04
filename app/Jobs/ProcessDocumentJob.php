<?php

namespace App\Jobs;

use App\Actions\ExtractLabTestTables;
use App\Enums\LabTestDocumentStatus;
use App\Models\LabTestDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        $this->document->fill([
            'status' => LabTestDocumentStatus::Pending,
        ])->save();

        $tables = $extractTablesAction($this->document);

        if ($tables->isNotEmpty()) {
            $this->document->fill([
                'status' => LabTestDocumentStatus::Extracted,
            ])->save();
        }

        foreach ($tables as $table) {
            ProcessDocumentTable::dispatch($table);
        }
    }
}
