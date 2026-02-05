<?php

namespace App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Pages;

use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\LabTestTableResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLabTestTable extends CreateRecord
{
    protected static string $resource = LabTestTableResource::class;
}
