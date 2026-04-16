<?php

namespace App\Filament\Resources\AiModelPricings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AiModelPricingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('model')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('prompt_token_price')
                    ->label('Prompt (per 1M)')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('completion_token_price')
                    ->label('Completion (per 1M)')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('thought_token_price')
                    ->label('Thought (per 1M)')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cache_read_token_price')
                    ->label('Cache Read (per 1M)')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cache_write_token_price')
                    ->label('Cache Write (per 1M)')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
