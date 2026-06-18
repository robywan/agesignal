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
    protected const ESCALATION_MODEL = 'google/gemini-3.5-flash';

    public function __construct(
        protected FlagDuplicateLabTestResultsAction $flagDuplicates,
    ) {}

    public function __invoke(LabTestResult $testResult)
    {
        $usageKey = "lab-document/{$testResult->table->document_id}/result/{$testResult->id}/classification";
        $payload = Collection::make($testResult)
            ->only(['name', 'unit_measure', 'value', 'reference_values', 'notes'])
            ->all();

        $firstResponse = $this->attemptClassification($testResult, $payload, $usageKey);
        $firstResult = $firstResponse->toArray();

        $escalated = false;
        $response = $firstResponse;
        $result = $firstResult;

        if (empty($result['loinc_code'])) {
            $escalated = true;
            $response = $this->attemptClassification($testResult, $payload, $usageKey, self::ESCALATION_MODEL);
            $result = $response->toArray();
        }

        $debugPayload = $this->buildDebugPayload(
            response: $response,
            payload: $payload,
            escalated: $escalated,
            firstResponse: $escalated ? $firstResponse : null,
        );

        if (! empty($result['loinc_code'])) {
            $testResult->fill([
                'loinc_num' => $result['loinc_code'],
                'loinc_status' => LabTestResultLoincStatus::Mapped,
                'loinc_justification' => $result['justification'] ?? null,
                'loinc_confidence_score' => $result['confidence_score'] ?? null,
                'loinc_escalated' => $escalated,
                'loinc_debug_payload' => $debugPayload,
            ]);
        } else {
            $testResult->fill([
                'loinc_status' => LabTestResultLoincStatus::Unmapped,
                'loinc_escalated' => $escalated,
                'loinc_debug_payload' => $debugPayload,
            ]);
        }

        $testResult->save();

        if ($testResult->loinc_num) {
            ($this->flagDuplicates)($testResult);
        }

        return $response;
    }

    protected function attemptClassification(
        LabTestResult $testResult,
        array $payload,
        string $usageKey,
        ?string $model = null,
    ): StructuredAgentResponse {
        try {
            return (new LabTestResultClassifier($usageKey))
                ->prompt(json_encode($payload, JSON_THROW_ON_ERROR), model: $model);
        } catch (Throwable $throwable) {
            $testResult->forceFill([
                'loinc_debug_payload' => [
                    'status' => 'exception',
                    'input' => $payload,
                    'model' => $model,
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
    }

    protected function buildDebugPayload(
        StructuredAgentResponse $response,
        array $payload,
        bool $escalated,
        ?StructuredAgentResponse $firstResponse,
    ): array {
        $debugPayload = [
            'status' => empty($response->toArray()['loinc_code']) ? 'unmapped' : 'mapped',
            'escalated' => $escalated,
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

        if ($escalated && $firstResponse !== null) {
            $debugPayload['first_attempt'] = [
                'structured_output' => $firstResponse->toArray(),
                'steps' => $firstResponse->steps->count(),
                'tool_calls' => $firstResponse->toolCalls->count(),
            ];
        }

        return $debugPayload;
    }
}
