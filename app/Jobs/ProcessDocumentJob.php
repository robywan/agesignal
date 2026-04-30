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

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 30;

    public function __construct(
        protected LabTestDocument $document
    ) {}

    public function handle(
        ExtractLabTestTables $extractTablesAction
    ): void {
        $this->document->update([
            'status' => LabTestDocumentStatus::Pending,
        ]);

        $tables = $extractTablesAction($this->document);

        if ($tables->isNotEmpty()) {
            $this->document->update([
                'status' => LabTestDocumentStatus::Extracted,
            ]);
        }

        foreach ($tables as $table) {
            ProcessDocumentTable::dispatch($table);
        }
    }
}
