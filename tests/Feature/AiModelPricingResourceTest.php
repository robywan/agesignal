<?php

use App\Filament\Resources\AiModelPricings\AiModelPricingResource;
use App\Filament\Resources\AiModelPricings\Pages\CreateAiModelPricing;
use App\Filament\Resources\AiModelPricings\Pages\EditAiModelPricing;
use App\Filament\Resources\AiModelPricings\Pages\ListAiModelPricings;
use App\Models\AiModelPricing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Prism\Prism\Enums\Provider;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

// ── List page ─────────────────────────────────────────────────────────────────

it('can render the list page', function () {
    $this->get(AiModelPricingResource::getUrl('index'))
        ->assertSuccessful();
});

it('lists existing pricing records', function () {
    $pricings = AiModelPricing::factory()->count(3)->create();

    Livewire::test(ListAiModelPricings::class)
        ->assertCanSeeTableRecords($pricings);
});

// ── Create page ───────────────────────────────────────────────────────────────

it('can render the create page', function () {
    $this->get(AiModelPricingResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create a new pricing record', function () {
    $data = [
        'provider'                => Provider::Anthropic->value,
        'model'                   => 'claude-3-5-sonnet',
        'prompt_token_price'      => '3.00000000',
        'completion_token_price'  => '15.00000000',
        'thought_token_price'     => '0.00000000',
        'cache_read_token_price'  => '0.30000000',
        'cache_write_token_price' => '3.75000000',
    ];

    Livewire::test(CreateAiModelPricing::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('ai_model_pricings', [
        'provider' => Provider::Anthropic->value,
        'model'    => 'claude-3-5-sonnet',
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreateAiModelPricing::class)
        ->fillForm([
            'provider' => null,
            'model'    => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['provider' => 'required', 'model' => 'required']);
});

it('enforces unique provider + model combination on create', function () {
    AiModelPricing::factory()->create([
        'provider' => Provider::OpenAI->value,
        'model'    => 'gpt-4o',
    ]);

    Livewire::test(CreateAiModelPricing::class)
        ->fillForm([
            'provider'               => Provider::OpenAI->value,
            'model'                  => 'gpt-4o',
            'prompt_token_price'     => '5.00000000',
            'completion_token_price' => '15.00000000',
        ])
        ->call('create')
        ->assertHasFormErrors(['model']);
});

// ── Edit page ─────────────────────────────────────────────────────────────────

it('can render the edit page', function () {
    $pricing = AiModelPricing::factory()->create();

    $this->get(AiModelPricingResource::getUrl('edit', ['record' => $pricing]))
        ->assertSuccessful();
});

it('fills the edit form with existing data', function () {
    $pricing = AiModelPricing::factory()->create([
        'provider'               => Provider::Gemini->value,
        'model'                  => 'gemini-2.0-flash',
        'prompt_token_price'     => '0.10000000',
        'completion_token_price' => '0.40000000',
    ]);

    Livewire::test(EditAiModelPricing::class, ['record' => $pricing->getRouteKey()])
        ->assertFormSet([
            'provider'               => Provider::Gemini->value,
            'model'                  => 'gemini-2.0-flash',
            'prompt_token_price'     => '0.10000000',
            'completion_token_price' => '0.40000000',
        ]);
});

it('can update a pricing record', function () {
    $pricing = AiModelPricing::factory()->create([
        'provider' => Provider::OpenAI->value,
        'model'    => 'gpt-4o-mini',
    ]);

    Livewire::test(EditAiModelPricing::class, ['record' => $pricing->getRouteKey()])
        ->fillForm([
            'prompt_token_price'     => '0.15000000',
            'completion_token_price' => '0.60000000',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($pricing->fresh()->prompt_token_price)->toBe('0.15000000');
});

// ── Delete ────────────────────────────────────────────────────────────────────

it('can delete a pricing record from the edit page', function () {
    $pricing = AiModelPricing::factory()->create();

    Livewire::test(EditAiModelPricing::class, ['record' => $pricing->getRouteKey()])
        ->callAction('delete');

    $this->assertModelMissing($pricing);
});

it('can bulk delete pricing records from the list page', function () {
    $pricings = AiModelPricing::factory()->count(3)->create();

    Livewire::test(ListAiModelPricings::class)
        ->callTableBulkAction('delete', $pricings);

    foreach ($pricings as $pricing) {
        $this->assertModelMissing($pricing);
    }
});
