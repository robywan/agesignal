<?php

namespace App\Filament\Resources\LabTestDocuments\Actions;

use App\Enums\LabTestResultNormalizationStatus;
use App\Jobs\NormalizeLabTestResultJob;
use App\Models\LabTestDocument;
use Filament\Actions\Action;

class NormalizeResultsAction
{
    public static function make($name = 'normalizeResults'): Action
    {
        return Action::make($name)
            ->label('Normalizza risultati')
            ->action(function (LabTestDocument $record) {
                foreach ($record->results as $result) {
                    if ($result->normalization_status === LabTestResultNormalizationStatus::Completed) {
                        continue; // Skip if already normalized
                    }

                    NormalizeLabTestResultJob::dispatch($result);
                }
            })
            ->successNotificationTitle('Normalizzazione dei risultati accodata');
    }
}
