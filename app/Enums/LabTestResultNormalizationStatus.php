<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;

enum LabTestResultNormalizationStatus: string implements HasColor
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getColor(): ?string
    {
        return match ($this) {
            self::Processing => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }
}
