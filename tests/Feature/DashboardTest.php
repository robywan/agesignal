<?php

use App\Enums\LabTestDocumentStatus;
use App\Models\LabTestResult;
use App\Models\LabTestTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('shows the empty state when the user has no referti', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Nessun referto caricato')
        ->assertSee(__('Carica un referto'));
});

it('renders score, banner and value bars for a populated referto', function () {
    $user = User::factory()->create([
        'name' => 'Mario Rossi',
        'height_cm' => 178,
        'weight_kg' => 75.0,
    ]);

    $this->actingAs($user);

    $document = $user->labTestDocuments()->create([
        'test_date' => '2026-04-20',
        'status' => LabTestDocumentStatus::Parsed,
    ]);

    $table = LabTestTable::query()->create([
        'document_id' => $document->id,
        'page_number' => 1,
        'markdown' => null,
        'cells' => [],
    ]);

    LabTestResult::query()->create([
        'table_id' => $table->id,
        'name' => 'Glucosio',
        'value' => '92',
        'numeric_value' => 92,
        'unit_measure' => 'mg/dL',
        'reference_min' => 70,
        'reference_max' => 100,
        'is_abnormal' => false,
    ]);

    LabTestResult::query()->create([
        'table_id' => $table->id,
        'name' => 'Colesterolo totale',
        'value' => '230',
        'numeric_value' => 230,
        'unit_measure' => 'mg/dL',
        'reference_min' => 0,
        'reference_max' => 200,
        'is_abnormal' => true,
    ]);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Mario Rossi')
        ->assertSee('Colesterolo totale')
        ->assertSee('Parametri fuori range')
        ->assertSee('20/04/2026')
        ->assertSee('BMI');
});
