<?php

namespace App\Jobs;

use App\Actions\ClassifyLabTestAction;
use App\Models\LabTestResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ClassifyLabTestResultJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected LabTestResult $labTestResult
    ) {}

    public function handle(ClassifyLabTestAction $action): void
    {
        $response = $action($this->labTestResult);

        $result = collect($response->structured ?? [])->first();

        if ($result && ! empty($result['loinc_code'])) {
            $this->labTestResult->update([
                'loinc_num' => $result['loinc_code'],
                'loinc_justification' => $result['justification'] ?? null,
                'loinc_confidence_score' => $result['confidence_score'] ?? null,
            ]);
        }
    }
}
