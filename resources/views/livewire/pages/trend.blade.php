<section class="w-full bg-page">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">

        {{-- Header --}}
        <header class="flex items-center justify-between gap-4 rounded-xl border border-default bg-surface px-5 py-3">
            <div>
                <p class="text-heading text-text-primary leading-none">{{ __('Andamento storico') }}</p>
                <p class="mt-1 text-caption text-text-secondary">
                    @if ($this->documents->count() >= 2)
                        {{ __(':n referti dal :first al :last', [
                            'n' => $this->documents->count(),
                            'first' => $this->documents->first()->test_date->format('d/m/Y'),
                            'last' => $this->documents->last()->test_date->format('d/m/Y'),
                        ]) }}
                    @else
                        {{ __('Servono almeno 2 referti per costruire un andamento.') }}
                    @endif
                </p>
            </div>
            <flux:link :href="route('dashboard')" wire:navigate class="text-caption">
                ← {{ __('Torna alla dashboard') }}
            </flux:link>
        </header>

        @if ($this->documents->count() < 2)
            <div class="rounded-xl border border-dashed border-gray-200 bg-surface p-10 text-center">
                <p class="text-heading text-text-primary">{{ __('Storico insufficiente') }}</p>
                <p class="mt-2 text-body text-text-secondary">
                    {{ __('Carica almeno un altro referto per visualizzare l\'andamento dei tuoi parametri.') }}
                </p>
                <div class="mt-5">
                    <flux:button :href="route('documents.create')" variant="primary" wire:navigate>
                        {{ __('Carica un referto') }}
                    </flux:button>
                </div>
            </div>
        @else
            {{-- Filter chips --}}
            @php
                $filters = [
                    'all' => ['label' => __('Tutti'), 'count' => $this->counts['all']],
                    'rising' => ['label' => __('In rialzo costante'), 'count' => $this->counts['rising']],
                    'falling' => ['label' => __('In discesa costante'), 'count' => $this->counts['falling']],
                    'abnormal' => ['label' => __('Fuori range'), 'count' => $this->counts['abnormal']],
                    'stable' => ['label' => __('Stabili'), 'count' => $this->counts['stable']],
                ];
            @endphp

            <div class="flex flex-wrap gap-2 rounded-xl border border-default bg-surface p-3">
                @foreach ($filters as $key => $f)
                    @php $isActive = $filter === $key; @endphp
                    <button type="button" wire:click="setFilter('{{ $key }}')"
                            class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-caption transition
                                {{ $isActive
                                    ? 'border-brand bg-brand text-white'
                                    : 'border-default bg-surface text-text-primary hover:bg-surface-muted' }}">
                        {{ $f['label'] }}
                        <span class="rounded-full px-1.5 text-[9px] {{ $isActive ? 'bg-white/20' : 'bg-surface-muted' }} ">{{ $f['count'] }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Trend list --}}
            <div class="flex flex-col rounded-xl border border-default bg-surface">
                @if ($this->filteredTrends->isEmpty())
                    <p class="py-10 text-center text-body text-text-secondary">
                        {{ __('Nessun parametro corrisponde al filtro corrente.') }}
                    </p>
                @else
                    {{-- Header row --}}
                    <div class="hidden border-b border-gray-100 px-5 py-2 text-caption uppercase tracking-wider text-text-secondary lg:grid lg:grid-cols-[minmax(0,1.2fr)_minmax(140px,1fr)_auto_auto_auto] lg:gap-4">
                        <div>{{ __('Parametro') }}</div>
                        <div>{{ __('Andamento') }}</div>
                        <div class="text-right">{{ __('Attuale') }}</div>
                        <div class="text-right">{{ __('Δ totale') }}</div>
                        <div class="text-right">{{ __('Trend') }}</div>
                    </div>

                    @foreach ($this->filteredTrends as $t)
                        @php
                            $unit = $t->unit ? ' '.$t->unit : '';
                            $deltaText = $t->deltaPctTotal !== null ? sprintf('%+.1f%%', $t->deltaPctTotal) : '—';
                            $arrow = match (true) {
                                $t->deltaPctTotal !== null && $t->deltaPctTotal > 0.5 => '↑',
                                $t->deltaPctTotal !== null && $t->deltaPctTotal < -0.5 => '↓',
                                default => '→',
                            };
                            $deltaColor = match ($t->latestSeverity) {
                                'critical' => 'text-status-low-text',
                                'warn' => 'text-status-warn-text',
                                default => 'text-status-ok-text',
                            };
                            $trendChip = match (true) {
                                $t->monotonic && $t->direction === 'up' => 'bg-status-warn-bg text-status-warn-text',
                                $t->monotonic && $t->direction === 'down' => 'bg-status-warn-bg text-status-warn-text',
                                ! $t->monotonic && abs($t->relativeSlope) < 0.005 => 'bg-status-ok-bg text-status-ok-text',
                                default => 'bg-surface-muted text-text-secondary',
                            };
                            $abnormalBadge = $t->abnormal ? ($t->latestSeverity === 'critical' ? 'bg-status-low-bg text-status-low-text' : 'bg-status-warn-bg text-status-warn-text') : null;
                        @endphp

                        <div class="grid items-center gap-2 border-t border-gray-100 px-5 py-3
                                    lg:grid-cols-[minmax(0,1.2fr)_minmax(140px,1fr)_auto_auto_auto] lg:gap-4 lg:py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-label text-text-primary" title="{{ $t->label }}">{{ $t->label }}</p>
                                @if ($t->referenceMin !== null || $t->referenceMax !== null)
                                    <p class="text-caption text-text-secondary">
                                        {{ __('Range') }}:
                                        @if ($t->referenceMin !== null && $t->referenceMax !== null)
                                            {{ rtrim(rtrim((string) $t->referenceMin, '0'), '.') }}–{{ rtrim(rtrim((string) $t->referenceMax, '0'), '.') }}{{ $unit }}
                                        @elseif ($t->referenceMax !== null)
                                            ≤ {{ rtrim(rtrim((string) $t->referenceMax, '0'), '.') }}{{ $unit }}
                                        @elseif ($t->referenceMin !== null)
                                            ≥ {{ rtrim(rtrim((string) $t->referenceMin, '0'), '.') }}{{ $unit }}
                                        @endif
                                    </p>
                                @endif
                            </div>

                            <x-sparkline :points="$t->points"
                                         :reference-min="$t->referenceMin"
                                         :reference-max="$t->referenceMax"
                                         class="h-10 w-full" />

                            <div class="text-right text-label text-text-primary tabular-nums">
                                {{ rtrim(rtrim(number_format($t->lastValue, 2, '.', ''), '0'), '.') }}{{ $unit }}
                                @if ($abnormalBadge)
                                    <span class="ml-1 inline-block rounded-full px-1.5 text-[9px] {{ $abnormalBadge }}">{{ __('Fuori') }}</span>
                                @endif
                            </div>

                            <div class="text-right text-label tabular-nums {{ $deltaColor }}">
                                {{ $arrow }} {{ $deltaText }}
                            </div>

                            <div class="text-right">
                                <span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-medium {{ $trendChip }}">
                                    {{ $t->trendLabel }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

        @endif
    </div>
</section>
