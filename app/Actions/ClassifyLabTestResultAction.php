<?php

namespace App\Actions;

use App\Ai\Agents\LabTestResultClassifier;
use App\Enums\LabTestResultLoincStatus;
use App\Models\LabTestResult;
use Illuminate\Support\Collection;

class ClassifyLabTestResultAction
{
    public function __invoke(LabTestResult $testResult)
    {
        $usageKey = "lab-document/{$testResult->table->document_id}/result/{$testResult->id}/classification";
        $payload = Collection::make($testResult)
            ->only(['name', 'unit_measure', 'value', 'reference_values', 'notes'])
            ->toJson();

        $response = new LabTestResultClassifier($usageKey)
            ->prompt($payload);

        $result = $response->toArray();

        if (! empty($result['loinc_code'])) {
            $testResult->fill([
                'loinc_num' => $result['loinc_code'],
                'loinc_status' => LabTestResultLoincStatus::Mapped,
                'loinc_justification' => $result['justification'] ?? null,
                'loinc_confidence_score' => $result['confidence_score'] ?? null,
            ]);

        } else {
            $testResult->fill([
                'loinc_status' => LabTestResultLoincStatus::Unmapped,
            ]);
        }

        $testResult->save();

        return $response;
    }
}
