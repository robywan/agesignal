<?php

namespace App\Filament\Resources\LabTestDocuments\Pages;

use App\Filament\Resources\LabTestDocuments\LabTestDocumentResource;
use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\LabTestTableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Table;

class ManageTables extends ManageRelatedRecords
{
    protected static string $resource = LabTestDocumentResource::class;

    protected static string $relationship = 'tables';

    protected static ?string $navigationLabel = 'Tables';

    protected static ?string $relatedResource = LabTestTableResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
