<?php

namespace App\Filament\Resources\AiModelPricings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Prism\Prism\Enums\Provider;

class AiModelPricingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('provider')
                    ->options(collect(Provider::cases())->mapWithKeys(fn (Provider $p) => [$p->value => $p->name]))
                    ->required()
                    ->searchable(),
                TextInput::make('model')
                    ->required()
                    ->maxLength(255),
                TextInput::make('prompt_token_price')
                    ->label('Prompt token price (per 1M)')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->default(0),
                TextInput::make('completion_token_price')
                    ->label('Completion token price (per 1M)')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->default(0),
                TextInput::make('thought_token_price')
                    ->label('Thought token price (per 1M)')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                TextInput::make('cache_read_token_price')
                    ->label('Cache read token price (per 1M)')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                TextInput::make('cache_write_token_price')
                    ->label('Cache write token price (per 1M)')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
            ]);
    }
}
