<?php

namespace App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Schemas;

use App\Models\LabTestTable;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Phiki\Grammar\Grammar;

class LabTestTableInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('document.name')
                    ->label('Document'),
                TextEntry::make('media.name')
                    ->label('File')
                    ->state(fn (LabTestTable $record) => $record->media?->name . pathinfo($record->media?->file_name, PATHINFO_EXTENSION))
                    ->placeholder('-'),
                TextEntry::make('page_number')
                    ->numeric(),
                Section::make('Table')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->columns(1)
                    ->components([
                        TextEntry::make('markdown')
                            ->hiddenLabel()
                            ->markdown()
                    ]),
                Section::make('Markdown')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->columns(1)
                    ->components([
                        CodeEntry::make('markdown')
                            ->hiddenLabel()
                            ->grammar(Grammar::Markdown)
                    ])
            ]);
    }
}
