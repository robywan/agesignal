<?php

namespace App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Pages;

use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\LabTestTableResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLabTestTable extends EditRecord
{
    protected static string $resource = LabTestTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
