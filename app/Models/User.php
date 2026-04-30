<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Sex;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'birthdate', 'sex', 'height_cm', 'weight_kg'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date',
            'sex' => Sex::class,
            'height_cm' => 'integer',
            'weight_kg' => 'decimal:2',
        ];
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Years between birthdate and today; null if birthdate not set.
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->birthdate ? (int) $this->birthdate->diffInYears(Carbon::now()) : null,
        );
    }

    /**
     * BMI = weight (kg) / height (m)^2; null if either missing.
     */
    protected function bmi(): Attribute
    {
        return Attribute::make(
            get: function (): ?float {
                if (! $this->height_cm || ! $this->weight_kg) {
                    return null;
                }

                $heightM = $this->height_cm / 100;

                return round((float) $this->weight_kg / ($heightM ** 2), 1);
            },
        );
    }

    /**
     * @return HasMany<LabTestDocument>
     */
    public function labTestDocuments(): HasMany
    {
        return $this->hasMany(LabTestDocument::class, 'owner_user_id');
    }

    /**
     * @return HasMany<UserCondition>
     */
    public function conditions(): HasMany
    {
        return $this->hasMany(UserCondition::class);
    }

    /**
     * @return HasMany<UserCondition>
     */
    public function activeConditions(): HasMany
    {
        return $this->conditions()->where('is_active', true);
    }
}
