<?php

namespace App\Jobs;

use App\Models\LabTestDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Kreuzberg\Kreuzberg;

class ExtractTablesFromLabTestDocumentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected LabTestDocument $labTestDocument
    ) { }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $kreuzberg = new Kreuzberg();

        foreach ($this->labTestDocument->getMedia('files') as $media) {
            $result = $kreuzberg->extractFile($media->getPath());

            foreach ($result->tables as $table) {
                $this->labTestDocument->tables()->updateOrCreate([
                    'media_id' => $media->id,
                    'page_number' => $table->pageNumber,
                ], [
                    'markdown' => $table->markdown,
                    'cells' => $table->cells,
                ]);
            }
        }
    }
}
