<?php

namespace App\Filament\Resources\AiModelPricings;

use App\Filament\Resources\AiModelPricings\Pages\CreateAiModelPricing;
use App\Filament\Resources\AiModelPricings\Pages\EditAiModelPricing;
use App\Filament\Resources\AiModelPricings\Pages\ListAiModelPricings;
use App\Filament\Resources\AiModelPricings\Schemas\AiModelPricingForm;
use App\Filament\Resources\AiModelPricings\Tables\AiModelPricingsTable;
use App\Models\AiModelPricing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AiModelPricingResource extends Resource
{
    protected static ?string $model = AiModelPricing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static \UnitEnum|string|null $navigationGroup = 'AI';

    protected static ?string $recordTitleAttribute = 'model';

    public static function form(Schema $schema): Schema
    {
        return AiModelPricingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiModelPricingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiModelPricings::route('/'),
            'create' => CreateAiModelPricing::route('/create'),
            'edit' => EditAiModelPricing::route('/{record}/edit'),
        ];
    }
}
