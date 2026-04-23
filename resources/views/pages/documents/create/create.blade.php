<section class="w-full">
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <div class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Nuovo referto') }}</flux:heading>
            <flux:subheading>
                {{ __('Carica il PDF del referto. Il documento verrà salvato e processato automaticamente per estrarre i dati.') }}
            </flux:subheading>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
            <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <form wire:submit="save" class="space-y-6">
                    <flux:field>
                        <flux:label>{{ __('Data del referto') }}</flux:label>
                        <flux:input wire:model="testDate" type="date" />
                        <flux:error name="testDate" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('PDF del referto') }}</flux:label>
                        <flux:input wire:model="documentFile" type="file" accept="application/pdf,.pdf" />
                        <flux:text>{{ __('Formato supportato: PDF. Dimensione massima: 10 MB.') }}</flux:text>
                        <flux:error name="documentFile" />
                    </flux:field>

                    <div class="flex items-center gap-3">
                        <flux:button variant="primary" type="submit">
                            {{ __('Carica referto') }}
                        </flux:button>

                        <flux:text wire:loading wire:target="documentFile,save">
                            {{ __('Upload in corso...') }}
                        </flux:text>
                    </div>
                </form>
            </div>

            <aside class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/70">
                <div class="space-y-2">
                    <flux:heading>{{ __('Dopo il caricamento') }}</flux:heading>
                    <flux:text>
                        {{ __('Il documento viene salvato come referto personale e passa subito alla coda di elaborazione.') }}
                    </flux:text>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950/40">
                        <flux:text>{{ __('Stato iniziale') }}</flux:text>
                        <flux:badge color="warning">{{ __('In attesa') }}</flux:badge>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950/40">
                        <flux:text>{{ __('Archivio referti') }}</flux:text>
                        <flux:link :href="route('documents.index')" wire:navigate>
                            {{ __('Apri lista') }}
                        </flux:link>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>