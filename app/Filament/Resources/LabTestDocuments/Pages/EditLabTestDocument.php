<?php

namespace App\Filament\Resources\LabTestDocuments\Pages;

use App\Filament\Resources\LabTestDocuments\Actions\ExtractTablesFromDocumentsAction;
use App\Filament\Resources\LabTestDocuments\LabTestDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLabTestDocument extends EditRecord
{
    protected static string $resource = LabTestDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExtractTablesFromDocumentsAction::make(),
            DeleteAction::make(),
        ];
    }
}
