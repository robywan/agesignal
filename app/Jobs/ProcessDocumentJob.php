<?php

namespace App\Jobs;

use App\Enums\LabTestDocumentStatus;
use App\Models\LabTestDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
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

    public function handle(): void
    {
        $this->document->fill([
            'status' => LabTestDocumentStatus::Pending,
        ])->save();

        $tables = new Collection;

        foreach ($this->document->getMedia('files') as $media) {
            $tables->add($this->document->tables()->updateOrCreate(
                ['media_id' => $media->id, 'page_number' => null],
                []
            ));
        }

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
