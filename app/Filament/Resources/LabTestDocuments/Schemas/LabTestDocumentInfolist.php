<?php

namespace App\Filament\Resources\LabTestDocuments\Schemas;

use App\Models\AiUsage;
use App\Models\LabTestDocument;
use App\Models\LabTestResult;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LabTestDocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('owner.name'),
                TextEntry::make('test_date')
                    ->date(),
            ]);
    }
}
