<?php

use App\Actions\ClassifyLabTestResultAction;
use App\Ai\Agents\LabTestResultClassifier;
use App\Enums\LabTestResultLoincStatus;
use App\Models\LabTestResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Prompts\AgentPrompt;

uses(RefreshDatabase::class);

function loincArray(string $code = '2093-3'): array
{
    return [
        'original_name' => 'Colesterolo',
        'loinc_code' => $code,
        'justification' => 'Cholesterol in Ser/Plas, Qn',
        'confidence_score' => 0.95,
    ];
}

function emptyLoincArray(): array
{
    return [
        'original_name' => 'Sconosciuto',
        'loinc_code' => '',
        'justification' => 'Nessun candidato trovato',
        'confidence_score' => 0.0,
    ];
}

it('maps a result when the first attempt returns a loinc code', function () {
    LabTestResultClassifier::fake(fn () => loincArray('2093-3'));

    $result = LabTestResult::factory()->create();

    app(ClassifyLabTestResultAction::class)($result);

    $result->refresh();

    expect($result->loinc_status)->toBe(LabTestResultLoincStatus::Mapped)
        ->and($result->loinc_num)->toBe('2093-3')
        ->and((float) $result->loinc_confidence_score)->toBe(0.95)
        ->and($result->loinc_debug_payload['escalated'])->toBeFalse()
        ->and($result->loinc_debug_payload)->not->toHaveKey('first_attempt');

    LabTestResultClassifier::assertPrompted(fn (AgentPrompt $prompt) => $prompt->contains('name'));
});

it('escalates to a stronger model when the first attempt returns no loinc code', function () {
    $callCount = 0;

    LabTestResultClassifier::fake(function () use (&$callCount) {
        $callCount++;

        return $callCount === 1 ? emptyLoincArray() : loincArray('2093-3');
    });

    $result = LabTestResult::factory()->create();

    app(ClassifyLabTestResultAction::class)($result);

    $result->refresh();

    expect($result->loinc_status)->toBe(LabTestResultLoincStatus::Mapped)
        ->and($result->loinc_num)->toBe('2093-3')
        ->and($result->loinc_debug_payload['escalated'])->toBeTrue()
        ->and($result->loinc_debug_payload)->toHaveKey('first_attempt')
        ->and($callCount)->toBe(2);
});

it('marks a result as unmapped when both attempts return no loinc code', function () {
    LabTestResultClassifier::fake(fn () => emptyLoincArray());

    $result = LabTestResult::factory()->create();

    app(ClassifyLabTestResultAction::class)($result);

    $result->refresh();

    expect($result->loinc_status)->toBe(LabTestResultLoincStatus::Unmapped)
        ->and($result->loinc_num)->toBeNull()
        ->and($result->loinc_debug_payload['escalated'])->toBeTrue()
        ->and($result->loinc_debug_payload['status'])->toBe('unmapped');
});

it('saves an exception payload and rethrows when the agent throws', function () {
    LabTestResultClassifier::fake(function (): never {
        throw new RuntimeException('Provider overloaded');
    });

    $result = LabTestResult::factory()->create();

    expect(fn () => app(ClassifyLabTestResultAction::class)($result))
        ->toThrow(RuntimeException::class, 'Provider overloaded');

    $result->refresh();

    expect($result->loinc_debug_payload['status'])->toBe('exception')
        ->and($result->loinc_debug_payload['exception']['message'])->toBe('Provider overloaded');
});
