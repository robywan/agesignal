<?php

namespace App\Actions;

use App\Models\LabTestDocument;
use Illuminate\Database\Eloquent\Collection;
use Kreuzberg\Kreuzberg;

class ExtractLabTestTables
{
    public function __invoke(LabTestDocument $document): Collection
    {
        $kreuzberg = new Kreuzberg();
        $tables = new Collection();

        foreach ($document->getMedia('files') as $media) {
            $result = $kreuzberg->extractFile($media->getPath());

            foreach ($result->tables as $table) {
                $tables->add($document->tables()->updateOrCreate([
                    'media_id' => $media->id,
                    'page_number' => $table->pageNumber,
                ], [
                    'markdown' => $table->markdown,
                    'cells' => $table->cells,
                ]));
            }
        }

        return $tables;
    }
}