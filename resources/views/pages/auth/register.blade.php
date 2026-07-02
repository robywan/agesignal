<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Crea il tuo account')" :description="__('Inserisci i tuoi dati per iniziare a tracciare i referti.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="name"
                :label="__('Nome e cognome')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Mario Rossi')"
            />

            <flux:input
                name="email"
                :label="__('Email')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:input
                name="password_confirmation"
                :label="__('Conferma password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Conferma password')"
                viewable
            />

            <flux:separator text="{{ __('Profilo sanitario · facoltativo') }}" />

            <flux:text size="sm" class="text-text-secondary">
                {{ __('Servono per contestualizzare i tuoi valori (es. range di riferimento per fascia di età/genere). Puoi compilarli ora oppure dalle impostazioni del profilo.') }}
            </flux:text>

            <flux:input
                name="birthdate"
                :label="__('Data di nascita')"
                :value="old('birthdate')"
                type="date"
                autocomplete="bday"
            />

            <flux:select
                name="gender"
                :label="__('Genere')"
                :placeholder="__('Seleziona…')"
            >
                @foreach (\App\Enums\Gender::options() as $value => $label)
                    <flux:select.option value="{{ $value }}" :selected="old('gender') === $value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    name="height_cm"
                    :label="__('Altezza (cm)')"
                    :value="old('height_cm')"
                    type="number"
                    min="50"
                    max="260"
                    inputmode="numeric"
                    placeholder="175"
                />

                <flux:input
                    name="weight_kg"
                    :label="__('Peso (kg)')"
                    :value="old('weight_kg')"
                    type="number"
                    min="20"
                    max="400"
                    step="0.1"
                    inputmode="decimal"
                    placeholder="72.5"
                />
            </div>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Crea account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-text-secondary">
            <span>{{ __('Hai già un account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Accedi') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
