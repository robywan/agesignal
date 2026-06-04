<?php

namespace App\Actions;

use App\Ai\Agents\LabTestResultExtractor;
use App\Enums\LabTestResultRequestStatus;
use App\Models\LabTestTable;
use Illuminate\Database\Eloquent\Collection;

class ExtractLabTestResults
{
    public function __invoke(LabTestTable $table): Collection
    {
        $usageKey = "lab-document/{$table->document_id}/result/{$table->id}/results-extraction";

        $response = new LabTestResultExtractor($usageKey)
            ->prompt($table->markdown);

        $testResults = new Collection;
        $results = new Collection($response->toArray()['results'] ?? []);

        if ($results->isNotEmpty()) {
            $results
                ->each(function ($item) use ($table, $testResults) {
                    $testResults->add($table->results()->create([
                        'name' => $item['name'] ?? null,
                        'value' => $item['value'] ?? null,
                        'unit_measure' => $item['unit_measure'] ?? null,
                        'reference_values' => $item['reference_values'] ?? null,
                        'notes' => $item['notes'] ?? null,
                    ]));
                });

            $table->request_status = LabTestResultRequestStatus::Completed;

        } else {
            $table->request_status = LabTestResultRequestStatus::Failed;
        }

        $table->save();

        return $testResults;
    }
}
