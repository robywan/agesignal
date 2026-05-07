<section class="w-full bg-page">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">

        {{-- Header --}}
        <header class="flex items-center justify-between gap-4 rounded-xl border border-default bg-surface px-5 py-3">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-full bg-surface-muted text-label text-text-primary">
                    {{ $this->user->initials() }}
                </div>
                <div>
                    <p class="text-caption text-text-secondary">{{ __('Ciao,') }}</p>
                    <p class="text-heading text-text-primary leading-none">{{ $this->user->name }}</p>
                </div>
            </div>

            <div class="text-right">
                @if ($this->activeDocument?->test_date)
                    <p class="text-caption text-text-secondary">{{ __('Ultimo referto') }}</p>
                    <p class="text-label text-text-primary">{{ $this->activeDocument->test_date->format('d/m/Y') }}</p>
                @else
                    <flux:link :href="route('documents.create')" wire:navigate class="text-label">
                        {{ __('Carica un referto') }}
                    </flux:link>
                @endif
            </div>
        </header>

        @if (! $this->activeDocument)
            {{-- Empty state: no documents at all --}}
            <div class="rounded-xl border border-dashed border-gray-200 bg-surface p-10 text-center">
                <p class="text-heading text-text-primary">{{ __('Nessun referto caricato') }}</p>
                <p class="mt-2 text-body text-text-secondary">
                    {{ __('Carica il tuo primo PDF di laboratorio per visualizzare la dashboard.') }}
                </p>
                <div class="mt-5">
                    <flux:button :href="route('documents.create')" variant="primary" wire:navigate>
                        {{ __('Carica un referto') }}
                    </flux:button>
                </div>
            </div>
        @else
            {{-- Status banner --}}
            @php
                $banner = match ($this->dominantStatus) {
                    'critical' => ['bg' => 'bg-status-low-bg', 'text' => 'text-status-low-text', 'label' => __('Parametri da rivedere')],
                    'warn' => ['bg' => 'bg-status-warn-bg', 'text' => 'text-status-warn-text', 'label' => __('Parametri da monitorare')],
                    default => ['bg' => 'bg-status-ok-bg', 'text' => 'text-status-ok-text', 'label' => __('Tutti i parametri sono nella norma')],
                };
                $offCount = $this->statusCounts['warn'] + $this->statusCounts['critical'];
                $offNames = $this->outOfRange->take(3)->map(fn ($r) => $r->label)->implode(', ');
            @endphp

            <div class="rounded-xl px-5 py-3 {{ $banner['bg'] }}">
                <p class="text-label {{ $banner['text'] }}">
                    @if ($offCount > 0)
                        {{ trans_choice('{1} :count parametro fuori range|[2,*] :count parametri fuori range', $offCount, ['count' => $offCount]) }}
                    @else
                        {{ $banner['label'] }}
                    @endif
                </p>
                @if ($offCount > 0 && $offNames)
                    <p class="text-caption {{ $banner['text'] }} opacity-80">
                        {{ $offNames }}@if ($offCount > 3) {{ __('e altri') }}@endif
                    </p>
                @endif
            </div>

            {{-- Main grid --}}
            <div class="grid gap-4 lg:grid-cols-3">
                {{-- Main column (2/3) --}}
                <div class="flex flex-col gap-4 lg:col-span-2">

                    {{-- Parametri fuori range --}}
                    @if ($this->outOfRange->isNotEmpty())
                        <div class="rounded-xl border border-default bg-surface p-5">
                            <div class="mb-3 flex items-baseline justify-between">
                                <p class="text-label text-text-primary">{{ __('Parametri fuori range') }}</p>
                                <p class="text-caption text-text-secondary">
                                    {{ $this->outOfRange->count() }} {{ __('parametri') }}
                                </p>
                            </div>

                            <div class="divide-y divide-gray-100">
                                @foreach ($this->outOfRange as $row)
                                    @php
                                        $r = $row->result;
                                        $statusLabel = match ($row->severity) {
                                            'ok' => __('Normale'),
                                            'warn' => __('Attenzione'),
                                            'critical' => __('Alto'),
                                            default => null,
                                        };
                                    @endphp
                                    <x-value-bar
                                        :name="$row->label"
                                        :value="$r->numeric_value ?? $r->textual_value ?? $r->value"
                                        :unit="$r->unit_measure"
                                        :min="$r->reference_min"
                                        :max="$r->reference_max"
                                        :textual-range="$r->textual_range"
                                        :severity="$row->severity"
                                        :status-label="$statusLabel"
                                    />
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Risultati per categoria --}}
                    @if ($this->processedResults->isNotEmpty())
                        <div class="rounded-xl border border-default bg-surface p-5">
                            <p class="mb-3 text-label text-text-primary">{{ __('Risultati per categoria') }}</p>

                            <div class="flex flex-col gap-5">
                                @foreach ($this->resultsByCategory as $category => $rows)
                                    @php
                                        $catOk = $rows->where('severity', 'ok')->count();
                                        $catTotal = $rows->count();
                                    @endphp
                                    <div>
                                        <div class="mb-2 flex items-baseline justify-between border-b border-gray-100 pb-1">
                                            <p class="text-caption uppercase tracking-wider text-text-secondary">{{ $category }}</p>
                                            <p class="text-caption text-text-secondary">{{ $catOk }}/{{ $catTotal }} {{ __('nella norma') }}</p>
                                        </div>

                                        <div class="divide-y divide-gray-100">
                                            @foreach ($rows as $row)
                                                @php
                                                    $r = $row->result;
                                                    $statusLabel = match ($row->severity) {
                                                        'ok' => __('Normale'),
                                                        'warn' => __('Attenzione'),
                                                        'critical' => __('Alto'),
                                                        default => null,
                                                    };
                                                @endphp
                                                <x-value-bar
                                                    :name="$row->label"
                                                    :value="$r->numeric_value ?? $r->textual_value ?? $r->value"
                                                    :unit="$r->unit_measure"
                                                    :min="$r->reference_min"
                                                    :max="$r->reference_max"
                                                    :textual-range="$r->textual_range"
                                                    :severity="$row->severity"
                                                    :status-label="$statusLabel"
                                                />
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Andamento storico (confronto col referto precedente) --}}
                    @if ($this->previousDocument)
                        @php
                            $prevDate = $this->previousDocument->test_date?->format('d/m/Y') ?? '—';
                            $currDate = $this->activeDocument->test_date?->format('d/m/Y') ?? '—';
                        @endphp

                        <div class="rounded-xl border border-default bg-surface p-5">
                            <div class="mb-3 flex items-baseline justify-between gap-3">
                                <p class="text-label text-text-primary">{{ __('Andamento storico') }}</p>
                                <div class="flex items-center gap-3">
                                    <p class="text-caption text-text-secondary">
                                        {{ $prevDate }} → {{ $currDate }}
                                    </p>
                                    <flux:link :href="route('andamento')" wire:navigate class="text-caption">
                                        {{ __('Vedi tutto') }} →
                                    </flux:link>
                                </div>
                            </div>

                            @if ($this->comparisonRows->isEmpty())
                                <p class="py-6 text-center text-caption text-text-secondary">
                                    {{ __('Nessun parametro confrontabile tra i due referti.') }}
                                </p>
                            @else
                                <div class="grid grid-cols-[minmax(0,1fr)_auto_auto_auto] gap-x-4 gap-y-1 text-body">
                                    <div class="text-caption uppercase tracking-wider text-text-secondary">{{ __('Parametro') }}</div>
                                    <div class="text-right text-caption uppercase tracking-wider text-text-secondary">{{ __('Precedente') }}</div>
                                    <div class="text-right text-caption uppercase tracking-wider text-text-secondary">{{ __('Attuale') }}</div>
                                    <div class="text-right text-caption uppercase tracking-wider text-text-secondary">{{ __('Δ') }}</div>

                                    @foreach ($this->paginatedComparison as $row)
                                        @php
                                            $arrow = $row->deltaAbs > 0 ? '↑' : ($row->deltaAbs < 0 ? '↓' : '→');
                                            $deltaColor = match ($row->severity) {
                                                'critical' => 'text-status-low-text',
                                                'warn' => 'text-status-warn-text',
                                                default => 'text-status-ok-text',
                                            };
                                            $unit = $row->unit ? ' '.$row->unit : '';
                                            $deltaPctText = $row->deltaPct !== null
                                                ? sprintf('%+.1f%%', $row->deltaPct)
                                                : '—';
                                        @endphp
                                        <div class="truncate border-t border-gray-100 py-1.5 text-text-primary" title="{{ $row->label }}">
                                            {{ $row->label }}
                                        </div>
                                        <div class="border-t border-gray-100 py-1.5 text-right text-text-secondary tabular-nums">
                                            {{ rtrim(rtrim(number_format($row->previous, 2, '.', ''), '0'), '.') }}{{ $unit }}
                                        </div>
                                        <div class="border-t border-gray-100 py-1.5 text-right text-text-primary tabular-nums">
                                            {{ rtrim(rtrim(number_format($row->current, 2, '.', ''), '0'), '.') }}{{ $unit }}
                                        </div>
                                        <div class="border-t border-gray-100 py-1.5 text-right tabular-nums {{ $deltaColor }}">
                                            {{ $arrow }} {{ $deltaPctText }}
                                        </div>
                                    @endforeach
                                </div>

                                @if ($this->comparePages > 1)
                                    <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                                        <button type="button" wire:click="previousComparePage"
                                                class="rounded-md border border-default bg-surface px-3 py-1.5 text-caption text-text-primary disabled:opacity-40"
                                                @disabled($this->comparePage === 0)>
                                            ← {{ __('Precedenti') }}
                                        </button>
                                        <p class="text-caption text-text-secondary">
                                            {{ __('Pagina :n di :tot', ['n' => $this->comparePage + 1, 'tot' => $this->comparePages]) }}
                                            <span class="opacity-60">·</span>
                                            {{ $this->comparisonRows->count() }} {{ __('parametri') }}
                                        </p>
                                        <button type="button" wire:click="nextComparePage"
                                                class="rounded-md border border-default bg-surface px-3 py-1.5 text-caption text-text-primary disabled:opacity-40"
                                                @disabled($this->comparePage >= $this->comparePages - 1)>
                                            {{ __('Successivi') }} →
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Side column (1/3) --}}
                <aside class="flex flex-col gap-4">

                    {{-- AI Care chat --}}
                    <div class="flex flex-col rounded-xl border border-default bg-surface p-5">
                        <div class="mb-3 flex items-center gap-2">
                            <p class="text-label text-text-primary">{{ __('AI Care') }}</p>
                            <span class="rounded-full bg-surface-muted px-2 py-0.5 text-[9px] font-medium uppercase tracking-wider text-text-secondary">
                                {{ __('Beta') }}
                            </span>
                        </div>

                        <div class="mb-3 flex max-h-72 flex-col gap-2 overflow-y-auto pr-1" id="ai-care-messages">
                            @if (empty($aiMessages))
                                <p class="py-2 text-body text-text-secondary">
                                    {{ __('Chiedimi cosa vuoi capire dai tuoi parametri. Spiego in modo semplice, senza gergo medico.') }}
                                </p>
                            @else
                                @foreach ($aiMessages as $msg)
                                    @if ($msg['role'] === 'user')
                                        <div class="self-end max-w-[85%] rounded-md bg-brand-light px-3 py-2 text-body text-brand-deep">
                                            {{ $msg['content'] }}
                                        </div>
                                    @else
                                        <div class="self-start max-w-[85%] rounded-md bg-surface-muted px-3 py-2 text-body text-text-primary whitespace-pre-line">
                                            {{ $msg['content'] }}
                                        </div>
                                    @endif
                                @endforeach
                            @endif

                            <div wire:loading wire:target="sendAiMessage" class="self-start max-w-[85%] rounded-md bg-surface-muted px-3 py-2 text-body text-text-secondary italic">
                                {{ __('Sto pensando…') }}
                            </div>
                        </div>

                        @if ($aiError)
                            <p class="mb-2 text-caption text-status-low-text">{{ $aiError }}</p>
                        @endif

                        <form wire:submit="sendAiMessage" class="flex items-center gap-2">
                            <input type="text"
                                   wire:model="aiInput"
                                   placeholder="{{ __('Scrivi una domanda…') }}"
                                   class="flex-1 rounded-md border border-default bg-surface px-3 py-2 text-body text-text-primary placeholder:text-text-secondary focus:outline-none focus:ring-2 focus:ring-brand/30"
                                   wire:loading.attr="disabled" wire:target="sendAiMessage" />
                            <button type="submit"
                                    class="rounded-md bg-brand px-3 py-2 text-caption font-medium text-white disabled:opacity-50"
                                    wire:loading.attr="disabled" wire:target="sendAiMessage">
                                {{ __('Invia') }}
                            </button>
                        </form>
                    </div>

                    {{-- BMI card --}}
                    <div class="rounded-xl border border-default bg-surface p-5">
                        <p class="mb-3 text-label text-text-primary">{{ __('BMI') }}</p>

                        @if ($this->user->bmi !== null)
                            <div class="flex items-baseline gap-2">
                                <p class="text-display text-text-primary">{{ number_format($this->user->bmi, 1) }}</p>
                                <x-status-badge :status="$this->bmiSeverity()" :label="$this->bmiLabel()" />
                            </div>
                            <p class="mt-2 text-caption text-text-secondary">
                                {{ __('Calcolato da :h cm e :w kg', ['h' => $this->user->height_cm, 'w' => number_format((float) $this->user->weight_kg, 1)]) }}
                            </p>
                        @else
                            <p class="text-body text-text-secondary">
                                {{ __('Aggiungi altezza e peso al tuo profilo per vedere il BMI.') }}
                            </p>
                            <div class="mt-3">
                                <flux:link :href="route('profile.edit')" wire:navigate class="text-caption">
                                    {{ __('Completa profilo') }}
                                </flux:link>
                            </div>
                        @endif
                    </div>

                    {{-- Cronologia referti --}}
                    @if ($this->documents->isNotEmpty())
                        <div class="rounded-xl border border-default bg-surface p-5">
                            <p class="mb-3 text-label text-text-primary">{{ __('Cronologia referti') }}</p>

                            <ul class="flex flex-col gap-1">
                                @foreach ($this->documents as $doc)
                                    @php
                                        $dotColor = match ($doc->status?->getColor()) {
                                            'success' => 'bg-status-ok',
                                            'warning' => 'bg-status-warn',
                                            'danger' => 'bg-status-low',
                                            'info' => 'bg-brand',
                                            default => 'bg-gray-400',
                                        };
                                        $isActive = $this->activeDocument?->id === $doc->id;
                                    @endphp
                                    <li>
                                        <button type="button" wire:click="selectDocument({{ $doc->id }})"
                                                class="flex w-full items-center gap-3 rounded-md px-2 py-1.5 text-left transition hover:bg-surface-muted {{ $isActive ? 'bg-surface-muted' : '' }}"
                                                @aria-current($isActive ? 'true' : 'false')>
                                            <span class="size-2 shrink-0 rounded-full {{ $dotColor }}"></span>
                                            <span class="flex flex-1 flex-col leading-tight">
                                                <span class="text-label text-text-primary">
                                                    {{ __('Test del :d', ['d' => $doc->test_date?->format('d/m/Y') ?? '—']) }}
                                                </span>
                                                <span class="text-caption text-text-secondary">{{ $doc->status?->label() }}</span>
                                            </span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-3 border-t border-gray-100 pt-3">
                                <flux:link :href="route('documents.index')" wire:navigate class="text-caption">
                                    {{ __('Vedi tutti i referti') }}
                                </flux:link>
                            </div>
                        </div>
                    @endif
                </aside>
            </div>

            {{-- Footer --}}
            <footer class="flex items-center justify-between rounded-xl border border-default bg-surface px-5 py-3">
                <p class="text-caption text-text-secondary">
                    {{ __('Caricato il :d', ['d' => $this->activeDocument->created_at->format('d/m/Y H:i')]) }}
                </p>

                @php($media = $this->activeDocument->getFirstMedia('files'))
                @if ($media)
                    <flux:button :href="$media->getUrl()" target="_blank" variant="ghost" size="sm" icon="arrow-down-tray">
                        {{ __('Scarica PDF') }}
                    </flux:button>
                @endif
            </footer>
        @endif
    </div>
</section>
