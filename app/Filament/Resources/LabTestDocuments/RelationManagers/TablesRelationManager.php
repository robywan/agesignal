<?php

namespace App\Filament\Resources\LabTestDocuments\RelationManagers;

use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\LabTestTableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TablesRelationManager extends RelationManager
{
    protected static string $relationship = 'tables';

    protected static ?string $relatedResource = LabTestTableResource::class;

    /*
    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
    */
}
