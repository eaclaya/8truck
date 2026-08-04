<?php

namespace App\Filament\Resources\Commissions;

use App\Filament\Resources\Commissions\Pages\ListCommissions;
use App\Models\Commission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['shipment:id,reference', 'transporterProfile.user:id,name'])
                ->latest())
            ->columns([
                TextColumn::make('shipment.reference')
                    ->limit(8),
                TextColumn::make('transporterProfile.user.name')
                    ->searchable(),
                TextColumn::make('base_amount')
                    ->money(fn (Commission $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('rate'),
                TextColumn::make('fee_amount')
                    ->money(fn (Commission $record): string => $record->currency)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommissions::route('/'),
        ];
    }
}
