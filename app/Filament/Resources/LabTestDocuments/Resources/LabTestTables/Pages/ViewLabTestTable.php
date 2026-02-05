<?php

namespace App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Pages;

use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\LabTestTableResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLabTestTable extends ViewRecord
{
    protected static string $resource = LabTestTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
