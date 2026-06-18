<?php

use App\Actions\NormalizeLabTestResultAction;
use App\Enums\LabTestResultNormalizationStatus;
use App\Jobs\NormalizeLabTestResultJob;
use App\Models\LabTestResult;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

it('dispatches the job', function () {
    $result = LabTestResult::factory()->create();

    NormalizeLabTestResultJob::dispatch($result);

    Queue::assertPushed(NormalizeLabTestResultJob::class, 1);
});

it('implements ShouldBeUnique', function () {
    expect(new NormalizeLabTestResultJob(LabTestResult::factory()->create()))
        ->toBeInstanceOf(ShouldBeUnique::class);
});

it('skips when normalization status is Completed', function () {
    $result = LabTestResult::factory()->create([
        'normalization_status' => LabTestResultNormalizationStatus::Completed,
    ]);

    $action = $this->mock(NormalizeLabTestResultAction::class);
    $action->shouldNotReceive('__invoke');

    $job = new NormalizeLabTestResultJob($result);
    $job->handle($action);

    expect($result->fresh()->normalization_status)->toBe(LabTestResultNormalizationStatus::Completed);
});

it('sets Processing status before calling action', function () {
    $result = LabTestResult::factory()->create([
        'normalization_status' => null,
    ]);

    $action = $this->mock(NormalizeLabTestResultAction::class);
    $action->shouldReceive('__invoke')
        ->once()
        ->withArgs(function (LabTestResult $arg) {
            return $arg->normalization_status === LabTestResultNormalizationStatus::Processing;
        })
        ->andReturn([]);

    $job = new NormalizeLabTestResultJob($result);
    $job->handle($action);
});

it('sets Failed status on job failure', function () {
    $result = LabTestResult::factory()->create([
        'normalization_status' => LabTestResultNormalizationStatus::Processing,
    ]);

    $job = new NormalizeLabTestResultJob($result);
    $job->failed(new RuntimeException('AI provider error'));

    expect($result->fresh()->normalization_status)->toBe(LabTestResultNormalizationStatus::Failed);
});
