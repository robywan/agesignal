<?php

namespace App\Filament\Resources\LabTestDocuments\Actions;

use App\Enums\LabTestResultLoincStatus;
use App\Jobs\ClassifyLabTestResultJob;
use App\Models\LabTestDocument;
use Filament\Actions\Action;

class ClassifyResultsAction
{
    public static function make($name = 'classifyResults'): Action
    {
        return Action::make($name)
            ->label('Classifica risultati')
            ->action(function (LabTestDocument $record) {
                foreach ($record->results as $result) {
                    if ($result->loinc_status === LabTestResultLoincStatus::Mapped) {
                        continue; // Skip if already classified
                    }
                    
                    ClassifyLabTestResultJob::dispatch($result);
                }
            })
            ->successNotificationTitle('Classificazione dei risultati accodata');
    }
}