<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profilo') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profilo')" :subheading="__('Le tue informazioni personali e i dati sanitari di base.')">

        {{-- ===== Account + profilo sanitario ===== --}}
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Nome e cognome')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <flux:text class="mt-4">
                        {{ __('Il tuo indirizzo email non è verificato.') }}
                        <flux:link class="cursor-pointer text-sm" wire:click.prevent="resendVerificationNotification">
                            {{ __('Invia di nuovo la mail di verifica.') }}
                        </flux:link>
                    </flux:text>
                @endif
            </div>

            <flux:separator text="{{ __('Profilo sanitario') }}" />

            <flux:text size="sm" class="text-text-secondary">
                {{ __('Servono per contestualizzare i tuoi valori (range di riferimento per fascia di età/genere, calcolo BMI). Puoi cambiarli quando vuoi.') }}
            </flux:text>

            <flux:input wire:model="birthdate" :label="__('Data di nascita')" type="date" autocomplete="bday" />

            <flux:select wire:model="gender" :label="__('Genere')">
                <flux:select.option value="">{{ __('Seleziona…') }}</flux:select.option>
                @foreach ($this->genderOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model.number="height_cm" :label="__('Altezza (cm)')" type="number" min="50" max="260" inputmode="numeric" placeholder="175" />
                <flux:input wire:model.number="weight_kg" :label="__('Peso (kg)')" type="number" min="20" max="400" step="0.1" inputmode="decimal" placeholder="72.5" />
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-profile-button">
                    {{ __('Salva') }}
                </flux:button>
            </div>
        </form>

        {{-- ===== Condizioni dichiarate ===== --}}
        <flux:separator text="{{ __('Condizioni dichiarate') }}" />

        <div class="my-6 w-full space-y-4">
            <flux:text size="sm" class="text-text-secondary">
                {{ __('Patologie croniche o condizioni rilevanti (es. diabete tipo 2, ipertensione, tiroidite). Aiutano a inquadrare i parametri delle analisi.') }}
            </flux:text>

            @if ($this->conditions->isEmpty())
                <div class="text-body text-text-secondary">
                    {{ __('Nessuna condizione dichiarata.') }}
                </div>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->conditions as $cond)
                        <div class="flex items-center gap-2 rounded-full bg-surface-muted py-1 pl-3 pr-1.5"
                             wire:key="cond-{{ $cond->id }}">
                            <span class="text-label text-text-primary">{{ $cond->name }}</span>
                            @if ($cond->since_year)
                                <span class="text-caption text-text-secondary">{{ __('dal') }} {{ $cond->since_year }}</span>
                            @endif
                            <button type="button"
                                    wire:click="removeCondition({{ $cond->id }})"
                                    wire:confirm="{{ __('Rimuovere questa condizione?') }}"
                                    class="ml-1 flex h-5 w-5 items-center justify-center rounded-full text-text-secondary hover:bg-status-low-bg hover:text-status-low-text"
                                    aria-label="{{ __('Rimuovi') }}">
                                <span style="font-size: 14px; line-height: 1;">×</span>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            <form wire:submit="addCondition" class="grid items-end gap-3 sm:grid-cols-[1fr_140px_auto]">
                <flux:input wire:model="newConditionName" :label="__('Aggiungi condizione')" type="text" :placeholder="__('Es. diabete tipo 2')" />
                <flux:input wire:model.number="newConditionSinceYear" :label="__('Dal (anno)')" type="number" min="1900" :max="date('Y')" :placeholder="date('Y')" />
                <flux:button type="submit">{{ __('Aggiungi') }}</flux:button>
            </form>
        </div>

        @if ($this->showDeleteUser)
            <livewire:settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
