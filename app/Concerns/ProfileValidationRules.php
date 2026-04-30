<?php

namespace App\Concerns;

use App\Enums\Sex;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait ProfileValidationRules
{
    /**
     * Base profile rules used at registration and profile editing.
     *
     * @param  int|null  $ignoreUserId  user id to exclude from email uniqueness check
     * @return array<string, array<int, ValidationRule|Enum|string>>
     */
    protected function profileRules(?int $ignoreUserId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($ignoreUserId),
            ],
            'birthdate' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'sex' => ['nullable', new Enum(Sex::class)],
            'height_cm' => ['nullable', 'integer', 'min:50', 'max:260'],
            'weight_kg' => ['nullable', 'numeric', 'min:20', 'max:400'],
        ];
    }
}
