<?php

namespace App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'requests';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('prompt_tokens')
                    ->numeric()
                    ->default(null),
                TextInput::make('completion_tokens')
                    ->numeric()
                    ->default(null),
                TextInput::make('thought_tokens')
                    ->numeric()
                    ->default(null),
                TextInput::make('cache_read_input_tokens')
                    ->numeric()
                    ->default(null),
                TextInput::make('cache_write_input_tokens')
                    ->numeric()
                    ->default(null),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('prompt_tokens')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('completion_tokens')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('thought_tokens')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('cache_read_input_tokens')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('cache_write_input_tokens')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('prompt_tokens')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('completion_tokens')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('thought_tokens')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cache_read_input_tokens')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cache_write_input_tokens')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
