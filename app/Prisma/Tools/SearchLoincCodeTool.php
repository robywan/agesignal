<?php

namespace App\Prisma\Tools;

use App\Models\LoincCoreEntry;
use Illuminate\Database\Eloquent\Builder;
use Prism\Prism\Facades\Tool;

class SearchLoincCodeTool
{
    public static function make()
    {
        return Tool::as('search_loinc_code')
            ->for('Cerca nel database locale i codici LOINC più pertinenti.')
            ->withStringParameter(
                'component',
                'Il nome dell\'esame (es: "Cholesterol", "Glucose")',
                true
            )
            ->withStringParameter(
                'system',
                'Il campione biologico, es: Ser/Plas, Stool, Urine'
            )
            ->withEnumParameter(
                'scale',
                'Tipo di risultato: Qn per numeri, Ord/Nom per testo o scale qualitative',
                ['Qn', 'Ord', 'Nom']
            )
            ->using(function (string $component, string $system, string $scale): string {
                $result = <<<'MARKDOWN'
                    | LOINC_NUM | COMPONENT | PROPERTY | SYSTEM | SCALE_TYPE |
                    |---|---|---|---|---|
                    MARKDOWN;

                $result .= "\n";

                $query = LoincCoreEntry::query()
                    ->where('scale_type', $scale)
                    ->where('status', 'ACTIVE')
                    // ->where('component', 'like', "%$component%")
                    // ->where('system', 'like', "%$system%")

                    ->where(fn (Builder $q) => $q
                        ->where('system', $system)
                        ->orWhere('system', 'LIKE', "%{$system}%"))

                    ->where(fn (Builder $q) => $q
                        ->where('component', $component) // esatto
                        ->orWhere('component', 'LIKE', "{$component}.%") // con suffisso (Cholesterol.total)
                        ->orWhere('component', 'LIKE', "%{$component}%") // contenuto
                        ->orWhere('long_common_name', 'LIKE', "%{$component}%")) // fallback

                    /*
                    ->orderByRaw("CASE WHEN component = ? THEN 0 ELSE 1 END", [$component]) // Prioritizza match esatti
                    ->orderByRaw("CASE WHEN system = ? THEN 0 ELSE 1 END", [$system]) // Prioritizza match esatti
                    ->orderByRaw("CASE WHEN scale_type = ? THEN 0 ELSE 1 END", [$scale]) // Prioritizza match esatti
                    ->orderBy('loinc_num') // Ordina per codice LOINC per stabilità
                    */
                    ->orderByRaw(<<<'SQL'
                        CASE 
                            WHEN component = ? THEN 1
                            WHEN component LIKE ? THEN 2
                            WHEN component LIKE ? THEN 3
                            WHEN long_common_name LIKE ? THEN 4
                            ELSE 5
                        END
                    SQL,
                        [
                            $component,
                            "{$component}.%",
                            "%{$component}%",
                            "%{$component}%",
                        ])

                    ->limit(10)
                    ->get(['loinc_num', 'component', 'property', 'system', 'scale_type'])
                    ->toArray();

                foreach ($query as $row) {
                    $result .= "| {$row['loinc_num']} | {$row['component']} | {$row['property']} | {$row['system']} | {$row['scale_type']} |\n";
                }

                return $result;
            });
    }
}
