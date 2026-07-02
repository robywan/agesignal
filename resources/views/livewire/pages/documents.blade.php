<section class="w-full">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <flux:heading size="xl">{{ __('Referti caricati') }}</flux:heading>
                <flux:subheading>
                    {{ __('Consulta i documenti che hai già caricato e controlla lo stato del processo di estrazione.') }}
                </flux:subheading>
            </div>

            <flux:link :href="route('documents.create')" wire:navigate>
                {{ __('Nuovo referto') }}
            </flux:link>
        </div>

        @if ($this->documents->isEmpty())
            <div class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 p-10 text-center dark:border-zinc-700 dark:bg-zinc-900/60">
                <flux:heading>{{ __('Nessun referto caricato') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Carica il tuo primo PDF per avviare il processo di estrazione.') }}</flux:text>
                <div class="mt-6">
                    <flux:link :href="route('documents.create')" wire:navigate>
                        {{ __('Carica un referto') }}
                    </flux:link>
                </div>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($this->documents as $document)
                    @php($media = $document->getFirstMedia('files'))

                    <article wire:key="document-{{ $document->id }}" class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-3">
                                    <flux:heading>{{ $document->name }}</flux:heading>
                                    <flux:badge color="{{ $document->status?->getColor() ?? 'gray' }}">
                                        {{ $document->status?->label() ?? __('Sconosciuto') }}
                                    </flux:badge>
                                </div>

                                <div class="grid gap-3 text-sm text-zinc-600 dark:text-zinc-300 sm:grid-cols-2 xl:grid-cols-4">
                                    <div>
                                        <p class="font-medium text-zinc-950 dark:text-white">{{ __('Data referto') }}</p>
                                        <p>{{ optional($document->test_date)?->format('d/m/Y') ?? __('Non indicata') }}</p>
                                    </div>

                                    <div>
                                        <p class="font-medium text-zinc-950 dark:text-white">{{ __('File') }}</p>
                                        <p>{{ $media?->file_name ?? __('PDF non disponibile') }}</p>
                                    </div>

                                    <div>
                                        <p class="font-medium text-zinc-950 dark:text-white">{{ __('Caricato il') }}</p>
                                        <p>{{ $document->created_at->format('d/m/Y H:i') }}</p>
                                    </div>

                                    <div>
                                        <p class="font-medium text-zinc-950 dark:text-white">{{ __('Aggiornato') }}</p>
                                        <p>{{ $document->updated_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="min-w-56 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                                <flux:text class="font-medium text-zinc-950 dark:text-white">{{ __('Stato del processo') }}</flux:text>
                                <flux:text class="mt-2">
                                    @if ($document->status === \App\Enums\LabTestDocumentStatus::Pending)
                                        {{ __('Il referto è stato salvato ed è in attesa di elaborazione.') }}
                                    @elseif ($document->status === \App\Enums\LabTestDocumentStatus::Extracted)
                                        {{ __('Le tabelle sono state estratte e il parsing delle analisi è in corso.') }}
                                    @else
                                        {{ __('L\'estrazione delle analisi è completata.') }}
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
