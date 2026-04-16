<?php

namespace App\Filament\Resources\LabTestDocuments;

use App\Filament\Resources\LabTestDocuments\Pages\CreateLabTestDocument;
use App\Filament\Resources\LabTestDocuments\Pages\EditLabTestDocument;
use App\Filament\Resources\LabTestDocuments\Pages\ListLabTestDocuments;
use App\Filament\Resources\LabTestDocuments\Pages\ManageResults;
use App\Filament\Resources\LabTestDocuments\Pages\ManageTables;
use App\Filament\Resources\LabTestDocuments\Pages\ViewLabTestDocument;
use App\Filament\Resources\LabTestDocuments\RelationManagers\TablesRelationManager;
use App\Filament\Resources\LabTestDocuments\Schemas\LabTestDocumentForm;
use App\Filament\Resources\LabTestDocuments\Schemas\LabTestDocumentInfolist;
use App\Filament\Resources\LabTestDocuments\Tables\LabTestDocumentsTable;
use App\Models\LabTestDocument;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LabTestDocumentResource extends Resource
{
    protected static ?string $model = LabTestDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Schema $schema): Schema
    {
        return LabTestDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LabTestDocumentsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LabTestDocumentInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLabTestDocuments::route('/'),
            'create' => CreateLabTestDocument::route('/create'),
            'view' => ViewLabTestDocument::route('/{record}'),
            'edit' => EditLabTestDocument::route('/{record}/edit'),
            'results' => ManageResults::route('/{record}/results'),
            'tables' => ManageTables::route('/{record}/tables'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewLabTestDocument::class,
            ManageResults::class,
            ManageTables::class,
        ]);
    }
}
