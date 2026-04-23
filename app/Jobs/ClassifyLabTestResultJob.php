<?php

namespace App\Jobs;

use App\Actions\ClassifyLabTestResultAction;
use App\Enums\LabTestResultLoincStatus;
use App\Models\LabTestResult;
use DateTime;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Throwable;

class ClassifyLabTestResultJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public $timeout = 300; // 5 minutes

    public function __construct(
        protected LabTestResult $labTestResult
    ) {}

    public function uniqueId(): string
    {
        return $this->labTestResult->id;
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(10)->toDateTime();
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
                    PrismProviderOverloadedException::class
                ])),
        ];
    }

    public function handle(ClassifyLabTestResultAction $action): void
    {
        if ($this->labTestResult->loinc_status === LabTestResultLoincStatus::Mapped) {
            return; // Skip if already classified
        }

        $this->labTestResult->update([
            'loinc_status' => LabTestResultLoincStatus::Processing
        ]);

        $action($this->labTestResult);
    }

    public function failed(?Throwable $exception): void
    {
        $this->labTestResult->update([
            'loinc_status' => LabTestResultLoincStatus::Failed
        ]);
    }
}
