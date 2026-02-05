<?php

namespace App\Filament\Resources\LabTestDocuments\Pages;

use App\Filament\Resources\LabTestDocuments\LabTestDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLabTestDocuments extends ListRecords
{
    protected static string $resource = LabTestDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
