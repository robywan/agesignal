<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;

enum LabTestResultRequestStatus: string implements HasColor
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function getColor(): ?string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }
}
