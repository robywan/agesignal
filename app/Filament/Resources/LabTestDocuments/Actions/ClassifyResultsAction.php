<?php

namespace App\Filament\Resources\LabTestDocuments\Actions;

use App\Enums\LabTestResultLoincStatus;
use App\Jobs\ClassifyLabTestResultJob;
use App\Models\LabTestDocument;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Support\Enums\Width;

class ClassifyResultsAction
{
    public static function make($name = 'classifyResults'): Action
    {
        return Action::make($name)
            ->label('Classifica risultati')
            ->modalWidth(Width::Small)
            ->schema([
                Radio::make('scope')
                    ->label('Risultati da classificare')
                    ->options([
                        'unmapped' => 'Solo non ancora classificati',
                        'all' => 'Tutti i risultati',
                    ])
                    ->default('unmapped')
                    ->required(),
            ])
            ->action(function (array $data, LabTestDocument $record) {
                foreach ($record->results as $result) {
                    if ($data['scope'] === 'unmapped' && $result->loinc_status === LabTestResultLoincStatus::Mapped) {
                        continue;
                    }

                    ClassifyLabTestResultJob::dispatch($result);
                }
            })
            ->successNotificationTitle('Classificazione dei risultati accodata');
    }
}
