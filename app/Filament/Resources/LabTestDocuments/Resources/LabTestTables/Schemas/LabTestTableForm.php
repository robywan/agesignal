<?php

namespace App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class LabTestTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('document_id')
                    ->relationship('document', 'id')
                    ->required(),
                Select::make('media_id')
                    ->relationship('media', 'name')
                    ->default(null),
                TextInput::make('page_number')
                    ->required()
                    ->numeric(),
                Textarea::make('markdown')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('cells')
                    ->required()
                    ->default('[]')
                    ->columnSpanFull(),
            ]);
    }
}
