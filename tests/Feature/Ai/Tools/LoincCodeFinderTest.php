<?php

use App\Ai\Tools\LoincCodeFinder;
use App\Models\LoincCoreEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;

uses(RefreshDatabase::class);

it('prioritizes platelet mean volume concepts for mpv queries', function () {
    LoincCoreEntry::factory()->create([
        'loinc_num' => '28542-9',
        'component' => 'Platelet',
        'property' => 'EntMeanVol',
        'time_aspect' => 'Pt',
        'system' => 'Bld',
        'scale_type' => 'Qn',
        'method_type' => null,
        'class' => 'HEM/BC',
        'class_type' => 1,
        'long_common_name' => 'Platelet [Entitic mean volume] in Blood',
        'short_name' => 'PMV Bld',
    ]);

    LoincCoreEntry::factory()->create([
        'loinc_num' => '32623-1',
        'component' => 'Platelet',
        'property' => 'EntMeanVol',
        'time_aspect' => 'Pt',
        'system' => 'Bld',
        'scale_type' => 'Qn',
        'method_type' => 'Automated count',
        'class' => 'HEM/BC',
        'class_type' => 1,
        'long_common_name' => 'Platelet [Entitic mean volume] in Blood by Automated count',
        'short_name' => 'PMV Bld Auto',
    ]);

    LoincCoreEntry::factory()->create([
        'loinc_num' => '99999-1',
        'component' => 'Human metapneumovirus A RNA',
        'property' => 'PrThr',
        'time_aspect' => 'Pt',
        'system' => 'Respiratory specimen',
        'scale_type' => 'Ord',
        'method_type' => null,
        'class' => 'MICRO',
        'class_type' => 1,
        'long_common_name' => 'Human metapneumovirus A RNA [Presence] in Respiratory specimen',
        'short_name' => 'aMPV RNA Resp Ql',
    ]);

    $results = invokeFinder([
        'component' => 'MPV',
        'system' => 'Bld/Plas',
        'scale' => 'Qn',
        'observed_value' => '10,2',
    ]);

    expect($results)
        ->not->toBeEmpty()
        ->and($results[0]['loinc_num'])->toBe('28542-9')
        ->and($results[0]['component'])->toBe('Platelet')
        ->and($results[0]['property'])->toBe('EntMeanVol')
        ->and(collect($results)->pluck('loinc_num'))->not->toContain('99999-1');
});

it('uses observational urine profiles for color measurements', function () {
    LoincCoreEntry::factory()->create([
        'loinc_num' => '5778-6',
        'component' => 'Observation',
        'property' => 'Color',
        'time_aspect' => 'Pt',
        'system' => 'Urine',
        'scale_type' => 'Nom',
        'method_type' => null,
        'class' => 'UA',
        'class_type' => 1,
        'long_common_name' => 'Color of Urine',
        'short_name' => 'Color Urine',
    ]);

    LoincCoreEntry::factory()->create([
        'loinc_num' => '88888-1',
        'component' => 'Color',
        'property' => 'Type',
        'time_aspect' => 'Pt',
        'system' => 'Eye',
        'scale_type' => 'Nom',
        'method_type' => null,
        'class' => 'EYE.CONTACT_LENS',
        'class_type' => 1,
        'long_common_name' => 'Color of contact lens',
        'short_name' => 'Contact lens color',
    ]);

    $results = invokeFinder([
        'component' => 'COLORE',
        'scale' => 'Nom',
        'observed_value' => 'Giallo paglierino',
    ]);

    expect($results)
        ->not->toBeEmpty()
        ->and($results[0]['loinc_num'])->toBe('5778-6')
        ->and($results[0]['component'])->toBe('Observation')
        ->and($results[0]['property'])->toBe('Color')
        ->and($results[0]['system'])->toBe('Urine');
});

it('does not rely on short-name matches for short acronyms', function () {
    LoincCoreEntry::factory()->create([
        'loinc_num' => '77777-1',
        'component' => 'Human metapneumovirus infection',
        'property' => 'PrThr',
        'time_aspect' => 'Pt',
        'system' => 'Respiratory specimen',
        'scale_type' => 'Ord',
        'method_type' => null,
        'class' => 'MICRO',
        'class_type' => 1,
        'long_common_name' => 'Human metapneumovirus infection panel',
        'short_name' => 'aMPV pnl',
    ]);

    $results = invokeFinder([
        'component' => 'MPV',
        'scale' => 'Qn',
    ]);

    expect($results)->toBeEmpty();
});

function invokeFinder(array $input): array
{
    $finder = new LoincCodeFinder;

    return json_decode($finder->handle(new Request($input)), true, flags: JSON_THROW_ON_ERROR);
}
