<?php

namespace App\Filament\Resources\LabTestDocuments\Actions;

use App\Jobs\ExtractTablesFromLabTestDocumentJob;
use App\Models\LabTestDocument;
use Filament\Actions\Action;

class ExtractTablesFromDocumentsAction
{
    public static function make($name = 'extractTablesFromDocuments'): Action
    {
        return Action::make($name)
            ->label('Estrai tabelle dai documenti')
            ->action(function (LabTestDocument $record) {
                ExtractTablesFromLabTestDocumentJob::dispatch($record);
            })
            ->successNotificationTitle('Estrazione delle tabelle accodata');
    }
}