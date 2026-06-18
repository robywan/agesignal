<?php

namespace App\Jobs;

use App\Actions\NormalizeLabTestResultAction;
use App\Enums\LabTestResultNormalizationStatus;
use App\Models\LabTestResult;
use DateTime;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Prism\Prism\Exceptions\PrismProviderOverloadedException;
use Prism\Prism\Exceptions\PrismRateLimitedException;
use Throwable;

class NormalizeLabTestResultJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes

    public function __construct(
        protected LabTestResult $labTestResult
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->labTestResult->id;
    }

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(10)->toDateTime();
    }

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

    public function handle(NormalizeLabTestResultAction $action): void
    {
        if ($this->labTestResult->normalization_status === LabTestResultNormalizationStatus::Completed) {
            return; // Skip if already normalized
        }

        $this->labTestResult->fill([
            'normalization_status' => LabTestResultNormalizationStatus::Processing,
        ])->save();

        $action($this->labTestResult);
    }

    public function failed(?Throwable $exception): void
    {
        $this->labTestResult->fill([
            'normalization_status' => LabTestResultNormalizationStatus::Failed,
        ])->save();
    }
}
