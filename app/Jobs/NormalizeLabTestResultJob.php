<?php

namespace App\Jobs;

use App\Actions\NormalizeLabTestResultAction;
use App\Models\LabTestResult;
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

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 30;

    public function __construct(
        protected LabTestResult $labTestResult
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->labTestResult->id;
    }

    /**
     * @return array<int, ThrottlesExceptions>
     */
    public function middleware(): array
    {
        return [
            (new ThrottlesExceptions(3, 5 * 60))
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
        if ($this->labTestResult->numeric_value !== null || $this->labTestResult->textual_value !== null) {
            return; // Skip if already normalized
        }

        $action($this->labTestResult);
    }
}
