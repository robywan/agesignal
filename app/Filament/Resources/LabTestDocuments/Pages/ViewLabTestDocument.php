<?php

namespace App\Filament\Resources\LabTestDocuments\Pages;

use App\Filament\Resources\LabTestDocuments\Actions\ProcessDocumentAction;
use App\Filament\Resources\LabTestDocuments\LabTestDocumentResource;
use App\Filament\Resources\LabTestDocuments\Widgets\TokenUsageOverview;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLabTestDocument extends ViewRecord
{
    protected static string $resource = LabTestDocumentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TokenUsageOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ProcessDocumentAction::make(),
            EditAction::make(),
        ];
    }
}
