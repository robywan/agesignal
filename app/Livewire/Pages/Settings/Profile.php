<?php

namespace App\Livewire\Pages\Settings;

use App\Concerns\ProfileValidationRules;
use App\Enums\Gender;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profilo')]
class Profile extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public ?string $birthdate = null;

    public ?string $gender = null;

    public ?int $height_cm = null;

    public ?float $weight_kg = null;

    public string $newConditionName = '';

    public ?int $newConditionSinceYear = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->birthdate = $user->birthdate?->format('Y-m-d');
        $this->gender = $user->gender?->value;
        $this->height_cm = $user->height_cm;
        $this->weight_kg = $user->weight_kg !== null ? (float) $user->weight_kg : null;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        // Normalize empty inputs so nullable validation accepts them
        $this->birthdate = $this->birthdate ?: null;
        $this->gender = $this->gender ?: null;

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profilo aggiornato.'));
    }

    public function addCondition(): void
    {
        $validated = $this->validate([
            'newConditionName' => ['required', 'string', 'max:120'],
            'newConditionSinceYear' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
        ]);

        $name = trim($validated['newConditionName']);

        $existing = Auth::user()->conditions()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            $existing->update([
                'is_active' => true,
                'since_year' => $validated['newConditionSinceYear'] ?? $existing->since_year,
            ]);
        } else {
            Auth::user()->conditions()->create([
                'name' => $name,
                'since_year' => $validated['newConditionSinceYear'] ?? null,
                'is_active' => true,
            ]);
        }

        $this->newConditionName = '';
        $this->newConditionSinceYear = null;
        unset($this->conditions);

        Flux::toast(variant: 'success', text: __('Condizione aggiunta.'));
    }

    public function removeCondition(int $id): void
    {
        Auth::user()->conditions()->whereKey($id)->update(['is_active' => false]);
        unset($this->conditions);

        Flux::toast(text: __('Condizione rimossa.'));
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: __('Una nuova mail di verifica è stata inviata al tuo indirizzo.'));
    }

    #[Computed]
    public function conditions()
    {
        return Auth::user()->activeConditions()->orderBy('name')->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function genderOptions(): array
    {
        return Gender::options();
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}
