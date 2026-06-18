<?php

namespace App\Filament\Resources\LabTestDocuments\Pages;

use App\Enums\LabTestResultLoincStatus;
use App\Filament\Resources\LabTestDocuments\Actions\ClassifyResultsAction;
use App\Filament\Resources\LabTestDocuments\Actions\NormalizeResultsAction;
use App\Filament\Resources\LabTestDocuments\LabTestDocumentResource;
use App\Models\LabTestResult;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Phiki\Grammar\Grammar;

class ManageResults extends ManageRelatedRecords
{
    protected static string $resource = LabTestDocumentResource::class;

    protected static string $relationship = 'results';

    protected static ?string $navigationLabel = 'Results';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected function getHeaderActions(): array
    {
        return [
            ClassifyResultsAction::make(),
            NormalizeResultsAction::make(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('table_id')
                    ->relationship('table', 'id')
                    ->required(),
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
                TextInput::make('loinc_num')
                    ->default(null),
                Textarea::make('loinc_justification')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('loinc_confidence_score')
                    ->numeric()
                    ->default(null),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextEntry::make('name'),
                TextEntry::make('loinc_num')
                    ->label('LOINC Core Entry')
                    ->columnSpan(2)
                    ->state(fn (LabTestResult $record) => sprintf(
                        '%s (%s%%)',
                        $record->loincCoreEntry?->long_common_name,
                        $record->loinc_confidence_score * 100
                    )),
                TextEntry::make('value')
                    ->label('Value')
                    ->columnStart(1),
                TextEntry::make('unit_measure'),
                TextEntry::make('reference_values'),
                TextEntry::make('notes')
                    ->columnSpanFull(),
                TextEntry::make('loinc_justification')
                    ->label('LOINC mapping justification')
                    ->columnSpanFull(),
                Section::make('LOINC classification debug payload')
                    ->columnSpanFull()
                    ->schema([
                        CodeEntry::make('loinc_debug_payload')
                            ->label('LOINC classification debug payload')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->grammar(Grammar::Json)
                            ->copyable(),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value')
                    ->state(fn (LabTestResult $record) => sprintf('%s %s', $record->value, $record->unit_measure))
                    ->searchable(),
                TextColumn::make('reference_values')
                    ->label('Reference Values')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('loinc_status')
                    ->label('LOINC')
                    ->badge(),
                IconColumn::make('loinc_escalated')
                    ->label('Escalation')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedArrowTrendingUp)
                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn ($state): string => match (true) {
                        $state === true => 'Classificato con escalation al modello potenziato',
                        $state === false => 'Classificato al primo tentativo',
                        default => '-',
                    })
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('normalization_status')
                    ->label('Norm.')
                    ->badge(),
                /*
                TextColumn::make('loincCoreEntry.short_name')
                    ->label('LOINC Core Entry')
                    ->state(fn (LabTestResult $record) => sprintf(
                        '[%s] %s (%s)',
                        $record->loincCoreEntry?->loinc_num,
                        $record->loincCoreEntry?->short_name,
                        $record->loinc_confidence_score ? sprintf('%s%%', $record->loinc_confidence_score * 100) : null
                    ))
                    ->searchable(),
                TextColumn::make('loinc_confidence_score')
                    ->label('Confidence')
                    ->state(fn (LabTestResult $record) => when($record->loinc_confidence_score, fn ($value) => $value * 100))
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                */
            ])
            ->filters([
                SelectFilter::make('loinc_status')
                    ->label('LOINC status')
                    ->options(LabTestResultLoincStatus::class),
                Filter::make('has_loinc')
                    ->label('With LOINC code')
                    ->query(fn ($query) => $query->whereNotNull('loinc_num')),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
