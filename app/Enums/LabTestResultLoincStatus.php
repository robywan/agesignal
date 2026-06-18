<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;

enum LabTestResultLoincStatus: string implements HasColor
{
    // case Pending = 'pending';
    case Processing = 'processing';
    case Mapped = 'mapped';
    case Unmapped = 'unmapped';
    case Failed = 'failed';
    case Duplicate = 'duplicate';

    public function getColor(): ?string
    {
        return match ($this) {
            self::Processing => 'info',
            self::Mapped => 'success',
            self::Unmapped => 'warning',
            self::Failed => 'danger',
            self::Duplicate => 'gray',
        };
    }
}
