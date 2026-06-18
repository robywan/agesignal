<?php

use App\Actions\FlagDuplicateLabTestResultsAction;
use App\Enums\LabTestResultLoincStatus;
use App\Models\LabTestDocument;
use App\Models\LabTestResult;
use App\Models\LabTestTable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function mappedResultForDocument(LabTestDocument $document, string $loincNum): LabTestResult
{
    $table = LabTestTable::factory()->create(['document_id' => $document->id]);

    return LabTestResult::factory()->create([
        'table_id' => $table->id,
        'loinc_num' => $loincNum,
        'loinc_status' => LabTestResultLoincStatus::Mapped,
    ]);
}

it('marks all results as duplicate when two results in the same document share the same loinc code', function () {
    $document = LabTestDocument::factory()->create();

    $resultA = mappedResultForDocument($document, '2093-3');
    $resultB = mappedResultForDocument($document, '2093-3');

    (new FlagDuplicateLabTestResultsAction)($resultA);

    expect($resultA->fresh()->loinc_status)->toBe(LabTestResultLoincStatus::Duplicate)
        ->and($resultB->fresh()->loinc_status)->toBe(LabTestResultLoincStatus::Duplicate);
});

it('does not affect results with different loinc codes in the same document', function () {
    $document = LabTestDocument::factory()->create();

    $resultA = mappedResultForDocument($document, '2093-3');
    $resultB = mappedResultForDocument($document, '718-7');

    (new FlagDuplicateLabTestResultsAction)($resultA);

    expect($resultA->fresh()->loinc_status)->toBe(LabTestResultLoincStatus::Mapped)
        ->and($resultB->fresh()->loinc_status)->toBe(LabTestResultLoincStatus::Mapped);
});

it('does not affect results with the same loinc code in different documents', function () {
    $documentA = LabTestDocument::factory()->create();
    $documentB = LabTestDocument::factory()->create();

    $resultA = mappedResultForDocument($documentA, '2093-3');
    $resultB = mappedResultForDocument($documentB, '2093-3');

    (new FlagDuplicateLabTestResultsAction)($resultA);

    expect($resultA->fresh()->loinc_status)->toBe(LabTestResultLoincStatus::Mapped)
        ->and($resultB->fresh()->loinc_status)->toBe(LabTestResultLoincStatus::Mapped);
});

it('is idempotent when run multiple times on already-duplicate results', function () {
    $document = LabTestDocument::factory()->create();

    $resultA = mappedResultForDocument($document, '2093-3');
    $resultB = mappedResultForDocument($document, '2093-3');

    $action = new FlagDuplicateLabTestResultsAction;
    $action($resultA);
    $action($resultA);

    expect($resultA->fresh()->loinc_status)->toBe(LabTestResultLoincStatus::Duplicate)
        ->and($resultB->fresh()->loinc_status)->toBe(LabTestResultLoincStatus::Duplicate);
});

it('restores a result to mapped when it becomes the sole result for that loinc in the document', function () {
    $document = LabTestDocument::factory()->create();

    $resultA = mappedResultForDocument($document, '2093-3');
    $resultB = mappedResultForDocument($document, '2093-3');

    $action = new FlagDuplicateLabTestResultsAction;
    $action($resultA);

    // Both are now duplicate. Delete one sibling to simulate it being removed.
    $resultB->delete();

    // Re-run the action on the surviving result (now in Duplicate state).
    $resultA->refresh();
    $action($resultA);

    expect($resultA->fresh()->loinc_status)->toBe(LabTestResultLoincStatus::Mapped);
});

it('does not change status when a result has no loinc code', function () {
    $result = LabTestResult::factory()->create([
        'loinc_num' => null,
        'loinc_status' => null,
    ]);

    (new FlagDuplicateLabTestResultsAction)($result);

    expect($result->fresh()->loinc_status)->toBeNull();
});
