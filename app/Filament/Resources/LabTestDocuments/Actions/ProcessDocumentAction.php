<?php

namespace App\Filament\Resources\LabTestDocuments\Actions;

use App\Jobs\ProcessDocumentJob;
use App\Models\LabTestDocument;
use Filament\Actions\Action;

class ProcessDocumentAction
{
    public static function make($name = 'processDocument'): Action
    {
        return Action::make($name)
            ->label('Processa documento')
            ->action(function (LabTestDocument $record) {
                ProcessDocumentJob::dispatch($record);
            })
            ->successNotificationTitle('Elaborazione del documento accodata');
    }
}