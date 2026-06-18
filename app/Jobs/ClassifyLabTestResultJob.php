<?php

namespace App\Jobs;

use App\Actions\ClassifyLabTestResultAction;
use App\Enums\LabTestResultLoincStatus;
use App\Models\LabTestResult;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Throwable;

class ClassifyLabTestResultJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    public int $backoff = 30;

    public function __construct(
        protected LabTestResult $labTestResult
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->labTestResult->id;
    }

    /* public function backoff(): array
    {
        return [1, 30, 60];
    }*/

    public function middleware(): array
    {
        return [
            new ThrottlesExceptions(3, 5 * 60)
                ->byJob()
                ->backoff(1)
                ->when(fn (Throwable $throwable) => in_array(get_class($throwable), [
                    PrismRateLimitedException::class,
                    PrismProviderOverloadedException::class,
                ])),
        ];
    }

    public function handle(ClassifyLabTestResultAction $action): void
    {
        $this->labTestResult->fill([
            'loinc_status' => LabTestResultLoincStatus::Processing,
        ])->save();

        $action($this->labTestResult);

        $this->labTestResult->refresh();

        NormalizeLabTestResultJob::dispatch($this->labTestResult);
    }

    public function failed(?Throwable $exception): void
    {
        $debugPayload = $this->labTestResult->loinc_debug_payload ?? [];

        $this->labTestResult->fill([
            'loinc_status' => LabTestResultLoincStatus::Failed,
            'loinc_debug_payload' => array_merge($debugPayload, [
                'job_failure' => [
                    'class' => $exception ? $exception::class : null,
                    'message' => $exception?->getMessage(),
                    'file' => $exception?->getFile(),
                    'line' => $exception?->getLine(),
                ],
            ]),
        ])->save();
    }
}
