<?php

use App\Actions\ExtractLabTestResults;
use App\Ai\Agents\LabTestDocumentExtractor;
use App\Ai\Agents\LabTestReferenceValuesExtractor;
use App\Enums\LabTestResultRequestStatus;
use App\Models\LabTestDocument;
use App\Models\LabTestTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

it('extracts lab test results from a PDF and creates result records', function () {
    $user = User::factory()->create();

    $document = LabTestDocument::factory()->create(['owner_user_id' => $user->id]);

    $media = Media::create([
        'model_type' => LabTestDocument::class,
        'model_id' => $document->id,
        'collection_name' => 'files',
        'name' => 'referto',
        'file_name' => 'referto.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => 1024,
        'manipulations' => '[]',
        'custom_properties' => '[]',
        'responsive_images' => '[]',
        'generated_conversions' => '[]',
        'order_column' => 1,
    ]);

    $table = LabTestTable::factory()->create([
        'document_id' => $document->id,
        'media_id' => $media->id,
    ]);

    LabTestDocumentExtractor::fake([[
        'results' => [
            [
                'name' => 'Emoglobina',
                'value' => '14.5',
                'unit_measure' => 'g/dL',
                'reference_values' => '12.0-16.0',
                'notes' => null,
            ],
            [
                'name' => 'Piastrine',
                'value' => '250',
                'unit_measure' => '10^3/µL',
                'reference_values' => '150-400',
                'notes' => 'Nella norma',
            ],
        ],
    ]]);

    $results = app(ExtractLabTestResults::class)($table);

    expect($results)->toHaveCount(2);

    $table->refresh();
    expect($table->request_status)->toBe(LabTestResultRequestStatus::Completed);

    $this->assertDatabaseHas('lab_test_results', [
        'table_id' => $table->id,
        'name' => 'Emoglobina',
        'value' => '14.5',
        'unit_measure' => 'g/dL',
        'reference_values' => '12.0-16.0',
        'notes' => null,
    ]);

    $this->assertDatabaseHas('lab_test_results', [
        'table_id' => $table->id,
        'name' => 'Piastrine',
        'value' => '250',
        'unit_measure' => '10^3/µL',
        'reference_values' => '150-400',
        'notes' => 'Nella norma',
    ]);
});

it('marks the table as failed when media is missing', function () {
    $user = User::factory()->create();
    $document = LabTestDocument::factory()->create(['owner_user_id' => $user->id]);

    $table = LabTestTable::factory()->create([
        'document_id' => $document->id,
        'media_id' => null,
    ]);

    $results = app(ExtractLabTestResults::class)($table);

    expect($results)->toBeEmpty();

    $table->refresh();
    expect($table->request_status)->toBe(LabTestResultRequestStatus::Failed);
});

it('marks the table as failed when ai returns no results', function () {
    $user = User::factory()->create();
    $document = LabTestDocument::factory()->create(['owner_user_id' => $user->id]);

    $media = Media::create([
        'model_type' => LabTestDocument::class,
        'model_id' => $document->id,
        'collection_name' => 'files',
        'name' => 'referto',
        'file_name' => 'referto.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => 1024,
        'manipulations' => '[]',
        'custom_properties' => '[]',
        'responsive_images' => '[]',
        'generated_conversions' => '[]',
        'order_column' => 1,
    ]);

    $table = LabTestTable::factory()->create([
        'document_id' => $document->id,
        'media_id' => $media->id,
    ]);

    LabTestDocumentExtractor::fake([['results' => []]]);

    $results = app(ExtractLabTestResults::class)($table);

    expect($results)->toBeEmpty();

    $table->refresh();
    expect($table->request_status)->toBe(LabTestResultRequestStatus::Failed);
});

it('recovers missing reference values with a second extraction pass', function () {
    $user = User::factory()->create();
    $document = LabTestDocument::factory()->create(['owner_user_id' => $user->id]);

    $media = Media::create([
        'model_type' => LabTestDocument::class,
        'model_id' => $document->id,
        'collection_name' => 'files',
        'name' => 'referto',
        'file_name' => 'referto.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => 1024,
        'manipulations' => '[]',
        'custom_properties' => '[]',
        'responsive_images' => '[]',
        'generated_conversions' => '[]',
        'order_column' => 1,
    ]);

    $table = LabTestTable::factory()->create([
        'document_id' => $document->id,
        'media_id' => $media->id,
    ]);

    LabTestDocumentExtractor::fake([[
        'results' => [
            [
                'name' => 'Emoglobina',
                'value' => '14.5',
                'unit_measure' => 'g/dL',
                'reference_values' => null,
                'notes' => null,
            ],
            [
                'name' => 'Piastrine',
                'value' => '250',
                'unit_measure' => '10^3/µL',
                'reference_values' => '150-400',
                'notes' => null,
            ],
        ],
    ]]);

    LabTestReferenceValuesExtractor::fake([[
        'results' => [
            ['name' => 'Emoglobina', 'reference_values' => '12.0-16.0'],
        ],
    ]]);

    $results = app(ExtractLabTestResults::class)($table);

    expect($results)->toHaveCount(2);

    $table->refresh();
    expect($table->request_status)->toBe(LabTestResultRequestStatus::Completed);

    $this->assertDatabaseHas('lab_test_results', [
        'table_id' => $table->id,
        'name' => 'Emoglobina',
        'reference_values' => '12.0-16.0',
    ]);

    $this->assertDatabaseHas('lab_test_results', [
        'table_id' => $table->id,
        'name' => 'Piastrine',
        'reference_values' => '150-400',
    ]);
});

it('leaves reference values null when recovery also finds nothing', function () {
    $user = User::factory()->create();
    $document = LabTestDocument::factory()->create(['owner_user_id' => $user->id]);

    $media = Media::create([
        'model_type' => LabTestDocument::class,
        'model_id' => $document->id,
        'collection_name' => 'files',
        'name' => 'referto',
        'file_name' => 'referto.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'public',
        'size' => 1024,
        'manipulations' => '[]',
        'custom_properties' => '[]',
        'responsive_images' => '[]',
        'generated_conversions' => '[]',
        'order_column' => 1,
    ]);

    $table = LabTestTable::factory()->create([
        'document_id' => $document->id,
        'media_id' => $media->id,
    ]);

    LabTestDocumentExtractor::fake([[
        'results' => [
            [
                'name' => 'Glucosio',
                'value' => '98',
                'unit_measure' => 'mg/dL',
                'reference_values' => null,
                'notes' => null,
            ],
        ],
    ]]);

    LabTestReferenceValuesExtractor::fake([[
        'results' => [
            ['name' => 'Glucosio', 'reference_values' => null],
        ],
    ]]);

    $results = app(ExtractLabTestResults::class)($table);

    expect($results)->toHaveCount(1);

    $table->refresh();
    expect($table->request_status)->toBe(LabTestResultRequestStatus::Completed);

    $this->assertDatabaseHas('lab_test_results', [
        'table_id' => $table->id,
        'name' => 'Glucosio',
        'reference_values' => null,
    ]);
});
