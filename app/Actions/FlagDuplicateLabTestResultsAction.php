<?php

namespace App\Actions;

use App\Enums\LabTestResultLoincStatus;
use App\Models\LabTestResult;
use Illuminate\Support\Facades\DB;

class FlagDuplicateLabTestResultsAction
{
    public function __invoke(LabTestResult $testResult): void
    {
        if (! $testResult->loinc_num) {
            return;
        }

        $documentId = $testResult->table->document_id;

        DB::transaction(function () use ($testResult, $documentId): void {
            $siblings = LabTestResult::query()
                ->whereHas(
                    'table',
                    fn ($q) => $q->where('document_id', $documentId)
                )
                ->where('loinc_num', $testResult->loinc_num)
                ->whereIn('loinc_status', [
                    LabTestResultLoincStatus::Mapped->value,
                    LabTestResultLoincStatus::Duplicate->value,
                ])
                ->lockForUpdate()
                ->get();

            if ($siblings->count() >= 2) {
                LabTestResult::query()
                    ->whereIn('id', $siblings->pluck('id'))
                    ->update(['loinc_status' => LabTestResultLoincStatus::Duplicate->value]);
            } else {
                LabTestResult::query()
                    ->whereIn('id', $siblings->pluck('id'))
                    ->update(['loinc_status' => LabTestResultLoincStatus::Mapped->value]);
            }
        });
    }
}
