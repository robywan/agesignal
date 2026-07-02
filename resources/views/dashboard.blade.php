@php
    $patient = auth()->user();
    $documents = $patient->labTestDocuments()->latest('test_date')->get();
    $latestDoc = $documents->first();
    $latestTestDate = $latestDoc?->test_date;
    $totalDocuments = $documents->count();

    $activeConditions = $patient->activeConditions()->orderBy('name')->get();

    $profileComplete = $patient->birthdate && $patient->gender && $patient->height_cm && $patient->weight_kg;

    $bmi = $patient->bmi;
    $bmiLabel = match (true) {
        $bmi === null => null,
        $bmi >= 30 => __('Obesità'),
        $bmi >= 25 => __('Sovrappeso'),
        $bmi >= 18.5 => __('Normopeso'),
        default => __('Sottopeso'),
    };
@endphp

<x-layouts::app :title="__('Dashboard')">
    <div class="flex w-full flex-col gap-2.5 p-2.5">

        {{-- ============ HEADER · profilo reale ============ --}}
        <header class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border-default bg-surface px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-light text-brand-deep"
                     style="font-weight: 500; font-size: 14px;">
                    {{ $patient->initials() }}
                </div>
                <div class="leading-tight">
                    <div class="text-heading text-text-primary">{{ $patient->name }}</div>
                    <div class="text-caption text-text-secondary">
                        @php
                            $bits = [];
                            if ($patient->age) {
                                $bits[] = __(':n anni', ['n' => $patient->age]);
                            }
                            if ($patient->gender) {
                                $bits[] = $patient->gender->label();
                            }
                            if ($bmi !== null) {
                                $bits[] = 'BMI '.number_format($bmi, 1).' · '.$bmiLabel;
                            }
                        @endphp
                        {{ $bits ? implode(' · ', $bits) : __('Profilo non ancora compilato') }}
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-caption text-text-secondary">{{ __('Ultimo referto') }}</div>
                <div class="text-text-primary" style="font-size: 11px; font-weight: 500;">
                    {{ $latestTestDate?->translatedFormat('d MMM Y') ?? __('Nessuno caricato') }}
                </div>
            </div>
        </header>

        {{-- ============ Banner profilo incompleto ============ --}}
        @unless ($profileComplete)
            <div class="rounded-xl border px-4 py-3"
                 style="background-color: var(--color-status-warn-bg); border-color: rgba(239,159,39,0.2);">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-label" style="color: var(--color-status-warn-text);">
                            {{ __('Completa il tuo profilo sanitario') }}
                        </div>
                        <div class="text-body" style="color: var(--color-status-warn-text); opacity: 0.85;">
                            {{ __('Età, genere, altezza e peso ci servono per applicare range di riferimento corretti ai tuoi parametri.') }}
                        </div>
                    </div>
                    <flux:link :href="route('profile.edit')" wire:navigate class="shrink-0">
                        {{ __('Vai al profilo') }}
                    </flux:link>
                </div>
            </div>
        @endunless

        {{-- ============ 2-column body ============ --}}
        <div class="grid gap-2.5" style="grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);">

            {{-- ===== LEFT COLUMN ===== --}}
            <div class="flex flex-col gap-2.5">

                {{-- Parametri (placeholder informato del prossimo passo) --}}
                <div class="rounded-xl border border-border-default bg-surface px-4 py-4">
                    <div class="text-caption text-text-secondary mb-3" style="letter-spacing: 0.05em; text-transform: uppercase;">
                        {{ __('Parametri delle analisi') }}
                    </div>
                    @if ($latestDoc)
                        <div class="text-body text-text-primary">
                            {{ __('Hai :n refert:p caricat:p.', ['n' => $totalDocuments, 'p' => $totalDocuments === 1 ? 'o' : 'i']) }}
                        </div>
                        <div class="text-body text-text-secondary mt-1">
                            {{ __('A breve in questa area i tuoi parametri saranno raggruppati per sistema (emocromo, funzione epatica, lipidi, tiroide, ecc.) con valori, range e andamento storico.') }}
                        </div>
                    @else
                        <div class="text-body text-text-primary">
                            {{ __('Nessun referto caricato.') }}
                        </div>
                        <div class="text-body text-text-secondary mt-1">
                            {{ __('Carica il primo PDF e troverai qui i tuoi parametri organizzati per sistema, con il loro andamento nel tempo.') }}
                        </div>
                        <div class="mt-3">
                            <flux:link :href="route('documents.create')" wire:navigate>
                                {{ __('Carica il primo referto') }}
                            </flux:link>
                        </div>
                    @endif
                </div>

                {{-- Storico referti --}}
                <div class="rounded-xl border border-border-default bg-surface px-4 py-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-caption text-text-secondary" style="letter-spacing: 0.05em; text-transform: uppercase;">
                            {{ __('Storico referti') }}
                        </div>
                        @if ($totalDocuments > 0)
                            <flux:link :href="route('documents.index')" wire:navigate class="text-caption">
                                {{ __('Tutti') }}
                            </flux:link>
                        @endif
                    </div>

                    @if ($documents->isEmpty())
                        <div class="text-body text-text-secondary">
                            {{ __('Nessun referto ancora caricato.') }}
                        </div>
                    @else
                        <div class="flex flex-col gap-1.5">
                            @foreach ($documents->take(5) as $doc)
                                <div class="flex items-center gap-2.5">
                                    <span class="block h-1.5 w-1.5 shrink-0 rounded-full bg-brand"></span>
                                    <div class="flex-1 leading-tight">
                                        <div class="text-label text-text-primary">{{ $doc->name }}</div>
                                        <div class="text-caption text-text-secondary">
                                            {{ $doc->test_date?->translatedFormat('d MMM Y') ?? __('Data non indicata') }}
                                        </div>
                                    </div>
                                    <x-status-badge
                                        :status="$doc->status?->value === 'completed' ? 'ok' : 'warn'"
                                        :label="$doc->status?->label() ?? __('Sconosciuto')"
                                    />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ===== RIGHT COLUMN ===== --}}
            <div class="flex flex-col gap-2.5">

                {{-- Condizioni dichiarate --}}
                <div class="rounded-xl border border-border-default bg-surface px-4 py-4">
                    <div class="text-caption text-text-secondary mb-3" style="letter-spacing: 0.05em; text-transform: uppercase;">
                        {{ __('Le mie condizioni') }}
                    </div>
                    @if ($activeConditions->isEmpty())
                        <div class="text-body text-text-secondary">
                            {{ __('Nessuna condizione dichiarata.') }}
                        </div>
                    @else
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($activeConditions as $cond)
                                <span class="rounded-full bg-surface-muted text-text-primary px-2.5 py-1 text-caption">
                                    {{ $cond->name }}@if ($cond->since_year) <span class="text-text-secondary">· {{ $cond->since_year }}</span>@endif
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- AI Care · placeholder onesto --}}
                <div class="rounded-xl border border-border-default bg-surface px-4 py-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-caption text-text-secondary" style="letter-spacing: 0.05em; text-transform: uppercase;">
                            AI Care
                        </div>
                        <span class="rounded-full bg-surface-muted text-text-secondary px-1.5 text-caption" style="font-size: 9px; padding-top: 2px; padding-bottom: 2px;">
                            {{ __('In arrivo') }}
                        </span>
                    </div>
                    <div class="text-body text-text-secondary">
                        {{ __('Quando sarà attivo, qui troverai un riepilogo in linguaggio piano dei tuoi referti, con suggerimenti su cosa chiedere al medico al prossimo controllo. Mai diagnosi, mai allarmi.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
