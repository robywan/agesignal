<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;

enum LabTestDocumentStatus: string implements HasColor
{
    case Pending = 'pending';
    case Extracted = 'extracted';
    case Parsed = 'parsed';

    public function getColor(): ?string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Extracted => 'info',
            self::Parsed => 'success',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In attesa',
            self::Extracted => 'Tabelle estratte',
            self::Parsed => 'Analisi estratte',
        };
    }
}
