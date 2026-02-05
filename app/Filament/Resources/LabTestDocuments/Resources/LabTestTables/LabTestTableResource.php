<?php

namespace App\Filament\Resources\LabTestDocuments\Resources\LabTestTables;

use App\Filament\Resources\LabTestDocuments\LabTestDocumentResource;
use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Pages\CreateLabTestTable;
use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Pages\EditLabTestTable;
use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Pages\ViewLabTestTable;
use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\RelationManagers\RequestsRelationManager;
use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\RelationManagers\ResultsRelationManager;
use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Schemas\LabTestTableForm;
use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Schemas\LabTestTableInfolist;
use App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Tables\LabTestTablesTable;
use App\Models\LabTestTable;
use BackedEnum;
use Filament\Resources\ParentResourceRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LabTestTableResource extends Resource
{
    protected static ?string $model = LabTestTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getParentResourceRegistration(): ?ParentResourceRegistration
    {
        return LabTestDocumentResource::asParent()
            ->relationship('tables')
            ->inverseRelationship('document');
    }

    public static function form(Schema $schema): Schema
    {
        return LabTestTableForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LabTestTableInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LabTestTablesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ResultsRelationManager::class,
            RequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'create' => CreateLabTestTable::route('/create'),
            'view' => ViewLabTestTable::route('/{record}'),
            'edit' => EditLabTestTable::route('/{record}/edit'),
        ];
    }
}
