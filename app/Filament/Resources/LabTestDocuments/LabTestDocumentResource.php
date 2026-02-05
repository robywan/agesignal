<?php

namespace App\Filament\Resources\LabTestDocuments;

use App\Filament\Resources\LabTestDocuments\Pages\CreateLabTestDocument;
use App\Filament\Resources\LabTestDocuments\Pages\EditLabTestDocument;
use App\Filament\Resources\LabTestDocuments\Pages\ListLabTestDocuments;
use App\Filament\Resources\LabTestDocuments\RelationManagers\TablesRelationManager;
use App\Filament\Resources\LabTestDocuments\Schemas\LabTestDocumentForm;
use App\Filament\Resources\LabTestDocuments\Tables\LabTestDocumentsTable;
use App\Models\LabTestDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LabTestDocumentResource extends Resource
{
    protected static ?string $model = LabTestDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LabTestDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LabTestDocumentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TablesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLabTestDocuments::route('/'),
            'create' => CreateLabTestDocument::route('/create'),
            'edit' => EditLabTestDocument::route('/{record}/edit'),
        ];
    }
}
