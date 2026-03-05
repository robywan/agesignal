<?php

namespace App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\RelationManagers;

use App\Jobs\ClassifyLabTestResultJob;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'results';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->default(null),
                TextInput::make('value')
                    ->default(null),
                TextInput::make('unit_measure')
                    ->default(null),
                TextInput::make('reference_values')
                    ->default(null),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->placeholder('-'),
                TextEntry::make('value')
                    ->placeholder('-'),
                TextEntry::make('unit_measure')
                    ->placeholder('-'),
                TextEntry::make('reference_values')
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
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
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('value')
                    ->searchable(),
                TextColumn::make('unit_measure')
                    ->searchable(),
                TextColumn::make('reference_values')
                    ->searchable(),
                TextColumn::make('loinc_num')
                    ->label('LOINC')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('loincCoreEntry.long_common_name')
                    ->label('LOINC Description')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable()
                    ->wrap(),
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
                Action::make('classify')
                    ->label('Classifica LOINC')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn ($record) => ClassifyLabTestResultJob::dispatch($record))
                    ->successNotificationTitle('Classificazione avviata'),
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
