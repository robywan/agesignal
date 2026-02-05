<?php

namespace App\Filament\Resources\LabTestDocuments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Schema;

class LabTestDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('owner_user_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->required(),
                DatePicker::make('test_date'),
                SpatieMediaLibraryFileUpload::make('files')
                    ->collection('files')
                    ->multiple(),
            ]);
    }
}
