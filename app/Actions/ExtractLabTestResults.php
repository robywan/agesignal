<?php

namespace App\Actions;

use App\Ai\Agents\LabTestDocumentExtractor;
use App\Ai\Agents\LabTestReferenceValuesExtractor;
use App\Enums\LabTestResultRequestStatus;
use App\Models\LabTestTable;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Files\Document;

class ExtractLabTestResults
{
    public function __invoke(LabTestTable $table): Collection
    {
        $media = $table->media;

        if (! $media) {
            $table->request_status = LabTestResultRequestStatus::Failed;
            $table->save();

            return new Collection;
        }

        $usageKey = "lab-document/{$table->document_id}/result/{$table->id}/results-extraction";

        $pdf = Document::fromPath($media->getPath());

        $response = new LabTestDocumentExtractor($usageKey)
            ->prompt('Estrai tutti i risultati delle analisi da questo referto di laboratorio.', [$pdf]);

        $testResults = new Collection;
        $results = new Collection($response->toArray()['results'] ?? []);

        if ($results->isNotEmpty()) {
            $results
                ->each(function ($item) use ($table, $testResults) {
                    $testResults->add($table->results()->create([
                        'name' => isset($item['name']) ? mb_substr($item['name'], 0, 255) : null,
                        'value' => isset($item['value']) ? mb_substr($item['value'], 0, 255) : null,
                        'unit_measure' => isset($item['unit_measure']) ? mb_substr($item['unit_measure'], 0, 255) : null,
                        'reference_values' => isset($item['reference_values']) ? mb_substr($item['reference_values'], 0, 255) : null,
                        'notes' => $item['notes'] ?? null,
                    ]));
                });

            $this->recoverMissingReferenceValues($testResults, $pdf, "lab-document/{$table->document_id}/result/{$table->id}");

            $table->request_status = LabTestResultRequestStatus::Completed;

        } else {
            $table->request_status = LabTestResultRequestStatus::Failed;
        }

        $table->save();

        return $testResults;
    }

    private function recoverMissingReferenceValues(Collection $testResults, Document $pdf, string $usageKeyPrefix): void
    {
        $incomplete = $testResults->filter(fn ($result) => $result->reference_values === null);

        if ($incomplete->isEmpty()) {
            return;
        }

        $names = $incomplete->pluck('name')->filter()->implode(', ');
        $usageKey = "{$usageKeyPrefix}/reference-values-recovery";

        $response = new LabTestReferenceValuesExtractor($usageKey)
            ->prompt("Trova i valori di riferimento per questi esami: {$names}", [$pdf]);

        $recovered = collect($response->toArray()['results'] ?? [])
            ->keyBy(fn ($item) => mb_strtolower(trim($item['name'] ?? '')));

        $incomplete->each(function ($result) use ($recovered) {
            $key = mb_strtolower(trim($result->name ?? ''));
            $match = $recovered->get($key);

            if ($match && ! empty($match['reference_values'])) {
                $result->reference_values = mb_substr($match['reference_values'], 0, 255);
                $result->save();
            }
        });
    }
}
