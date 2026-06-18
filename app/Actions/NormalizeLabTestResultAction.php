<?php

namespace App\Actions;

use App\Ai\Agents\LabTestResultNormalizer;
use App\Enums\LabTestResultNormalizationStatus;
use App\Models\LabTestResult;
use Throwable;

class NormalizeLabTestResultAction
{
    public function __invoke(LabTestResult $testResult): array
    {
        $usageKey = "lab-document/{$testResult->table->document_id}/result/{$testResult->id}/normalization";

        $payload = [
            'exam_name' => $testResult->name,
            'raw_value' => $testResult->value,
            'raw_range' => $testResult->reference_values,
            'loinc_scale_type' => $testResult->loincCoreEntry?->scale_type ?? 'Qn',
        ];

        try {
            $response = new LabTestResultNormalizer($usageKey)
                ->prompt(json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (Throwable $throwable) {
            $testResult->forceFill([
                'normalization_status' => LabTestResultNormalizationStatus::Failed,
            ])->save();

            throw $throwable;
        }

        $result = $response->toArray();

        $testResult->fill([
            'numeric_value' => $result['numeric_value'] ?? null,
            'operator' => $result['operator'] ?? null,
            'textual_value' => $result['textual_value'] ?? null,
            'is_abnormal' => $result['is_abnormal'] ?? false,
            'reference_min' => $result['reference_min'] ?? null,
            'reference_max' => $result['reference_max'] ?? null,
            'textual_range' => $result['textual_range'] ?? null,
            'normalization_status' => LabTestResultNormalizationStatus::Completed,
        ])->save();

        return $result;
    }
}
