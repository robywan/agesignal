<?php

namespace App\Ai\Tools;

use App\Models\LoincCoreEntry;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class LoincCodeFinder implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Cerca nel database locale i codici LOINC più pertinenti.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $component = (string) $request->string('component')->trim();
        $system = (string) $request->string('system')->trim();
        $scale = (string) $request->string('scale')->trim();
        $property = (string) $request->string('property')->trim();
        $observedValue = (string) $request->string('observed_value')->trim();
        $normalizedComponent = $this->normalizeTerm($component);
        $componentTokens = $this->extractSearchTokens($normalizedComponent);
        $allowShortNameSearch = $this->shouldSearchShortName($component, $normalizedComponent);
        $observationProfiles = $this->observationProfilesFor(
            normalizedComponent: $normalizedComponent,
            system: $system,
            scale: $scale,
            property: $property,
            observedValue: $observedValue,
        );
        $semanticProfiles = $this->semanticProfilesFor(
            component: $component,
            normalizedComponent: $normalizedComponent,
            system: $system,
            scale: $scale,
            property: $property,
        );

        $candidates = $this->buildPrimaryQuery(
            component: $component,
            normalizedComponent: $normalizedComponent,
            componentTokens: $componentTokens,
            system: $system,
            scale: $scale,
            property: $property,
            allowShortNameSearch: $allowShortNameSearch,
        )->get();

        $profileCandidates = $this->fetchProfileCandidates(
            profiles: [...$observationProfiles, ...$semanticProfiles],
            fallback: false,
        );

        if ($profileCandidates->isNotEmpty()) {
            $candidates = $candidates
                ->concat($profileCandidates)
                ->unique('loinc_num')
                ->sortByDesc('relevance_score')
                ->take(10)
                ->values();
        }

        if ($candidates->isEmpty() || ((int) ($candidates->first()->relevance_score ?? 0)) < 70) {
            $fallbackCandidates = $this->buildFallbackQuery(
                normalizedComponent: $normalizedComponent,
                componentTokens: $componentTokens,
                system: $system,
                scale: $scale,
                property: $property,
                allowShortNameSearch: $allowShortNameSearch,
            )->get();

            if ($observationProfiles !== [] || $semanticProfiles !== []) {
                $fallbackCandidates = $fallbackCandidates
                    ->concat($this->fetchProfileCandidates(
                        profiles: [...$observationProfiles, ...$semanticProfiles],
                        fallback: true,
                    ));
            }

            $candidates = $candidates
                ->concat($fallbackCandidates)
                ->unique('loinc_num')
                ->sortByDesc('relevance_score')
                ->take(10)
                ->values();
        }

        return $this->formatCandidates(
            candidates: $candidates,
            system: $system,
            scale: $scale,
            property: $property,
        )->toJson();
    }

    protected function buildPrimaryQuery(
        string $component,
        string $normalizedComponent,
        array $componentTokens,
        string $system,
        string $scale,
        string $property,
        bool $allowShortNameSearch,
    ): Builder {
        return LoincCoreEntry::query()
            ->where('status', 'ACTIVE')
            ->where(function (Builder $query) use ($component, $normalizedComponent, $componentTokens, $allowShortNameSearch): void {
                $query
                    ->where('component', $component)
                    ->orWhere('component', 'LIKE', "{$component}.%")
                    ->orWhere('component', 'LIKE', "%{$component}%")
                    ->orWhere('long_common_name', 'LIKE', "%{$component}%");

                if ($allowShortNameSearch) {
                    $query->orWhere('short_name', 'LIKE', "%{$component}%");
                }

                if ($normalizedComponent !== '' && $normalizedComponent !== $component) {
                    $query
                        ->orWhere('component', 'LIKE', "%{$normalizedComponent}%")
                        ->orWhere('long_common_name', 'LIKE', "%{$normalizedComponent}%");

                    if ($allowShortNameSearch) {
                        $query->orWhere('short_name', 'LIKE', "%{$normalizedComponent}%");
                    }
                }

                foreach ($componentTokens as $token) {
                    $query
                        ->orWhere('component', 'LIKE', "%{$token}%")
                        ->orWhere('long_common_name', 'LIKE', "%{$token}%");

                    if ($allowShortNameSearch) {
                        $query->orWhere('short_name', 'LIKE', "%{$token}%");
                    }
                }
            })
            ->selectRaw($this->relevanceSql(), $this->relevanceBindings(
                component: $component,
                normalizedComponent: $normalizedComponent,
                system: $system,
                scale: $scale,
                property: $property,
                allowShortNameSearch: $allowShortNameSearch,
            ))
            ->orderByDesc('relevance_score')
            ->orderBy('component')
            ->limit(10);
    }

    protected function buildFallbackQuery(
        string $normalizedComponent,
        array $componentTokens,
        string $system,
        string $scale,
        string $property,
        bool $allowShortNameSearch,
    ): Builder {
        return LoincCoreEntry::query()
            ->where('status', 'ACTIVE')
            ->where(function (Builder $query) use ($normalizedComponent, $componentTokens, $allowShortNameSearch): void {
                if ($normalizedComponent !== '') {
                    $query
                        ->where('component', 'LIKE', "%{$normalizedComponent}%")
                        ->orWhere('long_common_name', 'LIKE', "%{$normalizedComponent}%");

                    if ($allowShortNameSearch) {
                        $query->orWhere('short_name', 'LIKE', "%{$normalizedComponent}%");
                    }
                }

                foreach ($componentTokens as $token) {
                    $query
                        ->orWhere('component', 'LIKE', "%{$token}%")
                        ->orWhere('long_common_name', 'LIKE', "%{$token}%");

                    if ($allowShortNameSearch) {
                        $query->orWhere('short_name', 'LIKE', "%{$token}%");
                    }
                }
            })
            ->selectRaw(<<<'SQL'
                loinc_num,
                component,
                property,
                system,
                scale_type,
                long_common_name,
                short_name,
                method_type,
                class,
                time_aspect,
                (
                    CASE WHEN ? <> '' AND component LIKE ? THEN 35 ELSE 0 END +
                    CASE WHEN ? <> '' AND short_name LIKE ? THEN 28 ELSE 0 END +
                    CASE WHEN ? <> '' AND long_common_name LIKE ? THEN 22 ELSE 0 END +
                    CASE WHEN ? <> '' AND property = ? THEN 12 ELSE 0 END +
                    CASE WHEN ? <> '' AND property LIKE ? THEN 8 ELSE 0 END +
                    CASE WHEN ? <> '' AND system = ? THEN 10 ELSE 0 END +
                    CASE WHEN ? <> '' AND system LIKE ? THEN 6 ELSE 0 END +
                    CASE WHEN ? <> '' AND scale_type = ? THEN 5 ELSE 0 END
                ) AS relevance_score
            SQL,
                [
                    $normalizedComponent,
                    "%{$normalizedComponent}%",
                    $normalizedComponent,
                    "%{$normalizedComponent}%",
                    $normalizedComponent,
                    "%{$normalizedComponent}%",
                    $property,
                    $property,
                    $property,
                    "%{$property}%",
                    $system,
                    $system,
                    $system,
                    "%{$system}%",
                    $scale,
                    $scale,
                ])
            ->orderByDesc('relevance_score')
            ->orderBy('component')
            ->limit(10);
    }

    protected function relevanceSql(): string
    {
        return <<<'SQL'
            loinc_num,
            component,
            property,
            system,
            scale_type,
            long_common_name,
            short_name,
            method_type,
            class,
            time_aspect,
            (
                CASE WHEN component = ? THEN 100 ELSE 0 END +
                CASE WHEN component LIKE ? THEN 40 ELSE 0 END +
                CASE WHEN component LIKE ? THEN 20 ELSE 0 END +
                CASE WHEN ? <> '' AND component LIKE ? THEN 18 ELSE 0 END +
                CASE WHEN short_name LIKE ? THEN 20 ELSE 0 END +
                CASE WHEN ? <> '' AND short_name LIKE ? THEN 18 ELSE 0 END +
                CASE WHEN long_common_name LIKE ? THEN 15 ELSE 0 END +
                CASE WHEN ? <> '' AND long_common_name LIKE ? THEN 12 ELSE 0 END +
                CASE WHEN ? <> '' AND property = ? THEN 12 ELSE 0 END +
                CASE WHEN ? <> '' AND property LIKE ? THEN 8 ELSE 0 END +
                CASE WHEN ? <> '' AND system = ? THEN 10 ELSE 0 END +
                CASE WHEN ? <> '' AND system LIKE ? THEN 6 ELSE 0 END +
                CASE WHEN ? <> '' AND scale_type = ? THEN 5 ELSE 0 END
            ) AS relevance_score
        SQL;
    }

    protected function relevanceBindings(
        string $component,
        string $normalizedComponent,
        string $system,
        string $scale,
        string $property,
        bool $allowShortNameSearch,
    ): array {
        $shortNameComponent = $allowShortNameSearch ? "%{$component}%" : '';
        $shortNameNormalizedComponent = $allowShortNameSearch ? "%{$normalizedComponent}%" : '';

        return [
            $component,
            "{$component}.%",
            "%{$component}%",
            $normalizedComponent,
            "%{$normalizedComponent}%",
            $shortNameComponent,
            $normalizedComponent,
            $shortNameNormalizedComponent,
            $shortNameComponent,
            $normalizedComponent,
            "%{$normalizedComponent}%",
            $property,
            $property,
            $property,
            "%{$property}%",
            $system,
            $system,
            $system,
            "%{$system}%",
            $scale,
            $scale,
        ];
    }

    protected function formatCandidates(
        Collection $candidates,
        string $system,
        string $scale,
        string $property,
    ): Collection {
        return $candidates->map(function (LoincCoreEntry $candidate) use ($system, $scale, $property): array {
            $adjustedScore = $this->adjustedScore($candidate, $system, $scale, $property);
            $signals = array_values(array_filter([
                ((int) ($candidate->getAttribute('relevance_score') ?? 0)) >= 100 ? 'exact_component_match' : null,
                $property !== '' && $candidate->property === $property ? 'property_match' : null,
                $system !== '' && $candidate->system === $system ? 'system_match' : null,
                $scale !== '' && $candidate->scale_type === $scale ? 'scale_match' : null,
                $this->isPreferredLabClass((string) $candidate->getAttribute('class')) ? 'preferred_lab_class' : null,
                filled($candidate->short_name) ? 'has_short_name' : null,
                filled($candidate->long_common_name) ? 'has_long_common_name' : null,
            ]));

            return Arr::only([
                'loinc_num' => $candidate->loinc_num,
                'component' => $candidate->component,
                'property' => $candidate->property,
                'system' => $candidate->system,
                'scale_type' => $candidate->scale_type,
                'long_common_name' => $candidate->long_common_name,
                'short_name' => $candidate->short_name,
                'class' => $candidate->getAttribute('class'),
                'method_type' => $candidate->method_type,
                'time_aspect' => $candidate->time_aspect,
                'relevance_score' => $adjustedScore,
                'match_signals' => $signals,
            ], [
                'loinc_num',
                'component',
                'property',
                'system',
                'scale_type',
                'long_common_name',
                'short_name',
                'class',
                'method_type',
                'time_aspect',
                'relevance_score',
                'match_signals',
            ]);
        })->sortByDesc('relevance_score')->values();
    }

    protected function observationProfilesFor(
        string $normalizedComponent,
        string $system,
        string $scale,
        string $property,
        string $observedValue,
    ): array {
        $profiles = [];
        $urineLikely = $this->looksLikeUrineObservation($observedValue);
        $resolvedSystem = $system !== '' ? $system : ($urineLikely ? 'Urine' : '');
        $resolvedScale = $scale !== '' ? $scale : 'Nom';

        if (in_array($normalizedComponent, ['colore', 'color'], true)) {
            $profiles[] = [
                'component' => 'Observation',
                'property' => $property !== '' ? $property : 'Color',
                'system' => $resolvedSystem,
                'scale' => $resolvedScale,
            ];
        }

        if (in_array($normalizedComponent, ['aspetto', 'appearance'], true)) {
            $profiles[] = [
                'component' => 'Observation',
                'property' => $property !== '' ? $property : 'Aper',
                'system' => $resolvedSystem,
                'scale' => $resolvedScale,
            ];
        }

        if (in_array($normalizedComponent, ['limpidezza', 'torbidita', 'torbidità', 'clarity'], true)) {
            $profiles[] = [
                'component' => 'Clarity',
                'property' => $property !== '' ? $property : 'Type',
                'system' => $resolvedSystem,
                'scale' => $resolvedScale,
            ];
        }

        return $profiles;
    }

    protected function semanticProfilesFor(
        string $component,
        string $normalizedComponent,
        string $system,
        string $scale,
        string $property,
    ): array {
        $profiles = [];
        $resolvedScale = $scale !== '' ? $scale : 'Qn';
        $resolvedSystem = in_array($system, ['', 'Bld/Plas'], true) ? 'Bld' : $system;

        if ($this->looksLikeMeanPlateletVolume($component, $normalizedComponent)) {
            $profiles[] = [
                'component' => 'Platelet',
                'property' => $property !== '' ? $property : 'EntMeanVol',
                'system' => $resolvedSystem,
                'scale' => $resolvedScale,
            ];
        }

        return $profiles;
    }

    protected function adjustedScore(
        LoincCoreEntry $candidate,
        string $system,
        string $scale,
        string $property,
    ): int {
        $score = (int) ($candidate->getAttribute('relevance_score') ?? 0);
        $class = (string) $candidate->getAttribute('class');

        if ($this->isPreferredLabClass($class)) {
            $score += 25;
        }

        if ($this->isDiscouragedClass($class)) {
            $score -= 40;
        }

        if ($system === 'Urine' && $candidate->system === 'Urine') {
            $score += 20;
        }

        if ($scale !== '' && $candidate->scale_type === $scale) {
            $score += 5;
        }

        if ($property !== '' && $candidate->property === $property) {
            $score += 8;
        }

        return $score;
    }

    protected function fetchProfileCandidates(array $profiles, bool $fallback): Collection
    {
        return collect($profiles)
            ->flatMap(function (array $profile) use ($fallback) {
                $normalizedComponent = $this->normalizeTerm($profile['component']);
                $componentTokens = $this->extractSearchTokens($normalizedComponent);
                $allowShortNameSearch = $this->shouldSearchShortName($profile['component'], $normalizedComponent);

                $query = $fallback
                    ? $this->buildFallbackQuery(
                        normalizedComponent: $normalizedComponent,
                        componentTokens: $componentTokens,
                        system: $profile['system'],
                        scale: $profile['scale'],
                        property: $profile['property'],
                        allowShortNameSearch: $allowShortNameSearch,
                    )
                    : $this->buildPrimaryQuery(
                        component: $profile['component'],
                        normalizedComponent: $normalizedComponent,
                        componentTokens: $componentTokens,
                        system: $profile['system'],
                        scale: $profile['scale'],
                        property: $profile['property'],
                        allowShortNameSearch: $allowShortNameSearch,
                    );

                return $query->get();
            })
            ->values();
    }

    protected function isPreferredLabClass(string $class): bool
    {
        return in_array($class, ['UA', 'SPEC', 'CHEM', 'HEM/BC', 'MICRO'], true);
    }

    protected function isDiscouragedClass(string $class): bool
    {
        return in_array($class, ['EYE.CONTACT_LENS', 'H&P.HX', 'H&P.PX'], true);
    }

    protected function shouldSearchShortName(string $component, string $normalizedComponent): bool
    {
        if ($normalizedComponent === '') {
            return false;
        }

        if (preg_match('/^[a-z]{2,4}$/', $normalizedComponent) === 1) {
            return false;
        }

        return Str::length($normalizedComponent) >= 5 || Str::contains($component, [' ', '-', '/']);
    }

    protected function looksLikeMeanPlateletVolume(string $component, string $normalizedComponent): bool
    {
        return Str::contains($normalizedComponent, ['mpv', 'mean platelet volume', 'platelet mean volume', 'volume piastrinico'])
            || (Str::contains($normalizedComponent, 'piastrin') && Str::contains($normalizedComponent, 'volume'));
    }

    protected function looksLikeUrineObservation(string $observedValue): bool
    {
        $normalizedValue = $this->normalizeTerm($observedValue);

        return Str::contains($normalizedValue, [
            'giallo paglierino',
            'giallo citrino',
            'ambrato',
            'torbido',
            'limpido',
            'opalescente',
        ]);
    }

    protected function normalizeTerm(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/i', ' ')
            ->squish()
            ->value();
    }

    protected function extractSearchTokens(string $value): array
    {
        return Str::of($value)
            ->explode(' ')
            ->filter(fn (string $token): bool => Str::length($token) >= 3)
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'component' => $schema->string()
                ->description('Il nome dell\'esame (es: "Cholesterol", "Glucose")')
                ->required(),
            'system' => $schema->string()
                ->description('Il campione biologico, es: Ser/Plas, Stool, Urine')
                ->required(),
            'scale' => $schema->string()
                ->description('Tipo di risultato: Qn per numeri, Ord/Nom per testo o scale qualitative')
                ->enum(['Qn', 'Ord', 'Nom'])
                ->required(),
            'property' => $schema->string()
                ->description('Proprietà LOINC da privilegiare, es: MCnc, SCnc, ACnc, NCnc'),
            'observed_value' => $schema->string()
                ->description('Valore osservato del risultato, utile per inferire il dominio dell’osservazione'),
        ];
    }
}
