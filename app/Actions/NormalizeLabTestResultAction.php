<?php

namespace App\Actions;

use App\Ai\Agents\LabTestResultNormalizer;
use App\Models\LabTestResult;

class NormalizeLabTestResultAction
{
    public function __invoke(LabTestResult $testResult): array
    {
        $usageKey = "lab-document/{$testResult->table->document_id}/result/{$testResult->id}/normalization";

        return new LabTestResultNormalizer($usageKey)
            ->prompt(json_encode([
                'exam_name' => $testResult->name,
                'raw_value' => $testResult->value,
                'raw_range' => $testResult->reference_values,
                'loinc_scale_type' => $testResult->loincCoreEntry?->scale_type ?? 'Qn', // Default a Qn se non mappato
            ]))
            ->toArray();
    }
}
