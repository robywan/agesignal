<?php

namespace App\Filament\Resources\LabTestDocuments\Resources\LabTestTables\Tables;

use App\Enums\LabTestResultRequestStatus;
use App\Jobs\ProcessDocumentTable;
use App\Models\LabTestTable;
use Dom\Text;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LabTestTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                TextColumn::make('media.name')
                    ->label('File')
                    ->state(fn (LabTestTable $record) => $record->media?->name . pathinfo($record->media?->file_name, PATHINFO_EXTENSION))
                    ->searchable(),
                TextColumn::make('page_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('results_count')
                    ->label('Results')
                    ->counts('results')
                    ->sortable(),
                TextColumn::make('request_status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('process')
                    ->label('Process')
                    ->requiresConfirmation()
                    ->action(function (LabTestTable $record, Action $action) {
                        $record
                            ->forceFill(['request_status' => LabTestResultRequestStatus::Processing])
                            ->save();
                        
                        ProcessDocumentTable::dispatch($record);
                        
                        $action->success();
                    })
                    ->successNotificationTitle('Processo accodato'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
