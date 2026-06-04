<?php

namespace App\Actions;

use App\Ai\Agents\LabTestResultClassifier;
use App\Enums\LabTestResultLoincStatus;
use App\Models\LabTestResult;
use Illuminate\Support\Collection;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class ClassifyLabTestResultAction
{
    public function __invoke(LabTestResult $testResult)
    {
        $usageKey = "lab-document/{$testResult->table->document_id}/result/{$testResult->id}/classification";
        $payload = Collection::make($testResult)
            ->only(['name', 'unit_measure', 'value', 'reference_values', 'notes'])
            ->all();

        try {
            $response = new LabTestResultClassifier($usageKey)
                ->prompt(json_encode($payload, JSON_THROW_ON_ERROR));

        } catch (Throwable $throwable) {
            $testResult->forceFill([
                'loinc_debug_payload' => [
                    'status' => 'exception',
                    'input' => $payload,
                    'exception' => [
                        'class' => $throwable::class,
                        'message' => $throwable->getMessage(),
                        'file' => $throwable->getFile(),
                        'line' => $throwable->getLine(),
                    ],
                ],
            ])->save();

            throw $throwable;
        }

        $result = $response->toArray();
        $debugPayload = $this->buildDebugPayload($response, $payload);

        if (! empty($result['loinc_code'])) {
            $testResult->fill([
                'loinc_num' => $result['loinc_code'],
                'loinc_status' => LabTestResultLoincStatus::Mapped,
                'loinc_justification' => $result['justification'] ?? null,
                'loinc_confidence_score' => $result['confidence_score'] ?? null,
                'loinc_debug_payload' => $debugPayload,
            ]);

        } else {
            $testResult->fill([
                'loinc_status' => LabTestResultLoincStatus::Unmapped,
                'loinc_debug_payload' => $debugPayload,
            ]);
        }

        $testResult->save();

        return $response;
    }

    protected function buildDebugPayload(StructuredAgentResponse $response, array $payload): array
    {
        return [
            'status' => empty($response->toArray()['loinc_code']) ? 'unmapped' : 'mapped',
            'input' => $payload,
            'response_text' => $response->text,
            'structured_output' => $response->toArray(),
            'usage' => $response->usage->toArray(),
            'meta' => $response->meta->toArray(),
            'invocation_id' => $response->invocationId,
            'conversation_id' => $response->conversationId,
            'tool_calls' => $response->toolCalls
                ->map(fn ($toolCall) => $toolCall->toArray())
                ->values()
                ->all(),
            'tool_results' => $response->toolResults
                ->map(fn ($toolResult) => $toolResult->toArray())
                ->values()
                ->all(),
            'steps' => $response->steps
                ->map(fn ($step) => $step->toArray())
                ->values()
                ->all(),
        ];
    }
}
