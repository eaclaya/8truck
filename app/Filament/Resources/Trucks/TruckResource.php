<?php

namespace App\Filament\Resources\Trucks;

use App\Enums\TruckAvailability;
use App\Filament\Resources\Trucks\Pages\ListTrucks;
use App\Models\Truck;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TruckResource extends Resource
{
    protected static ?string $model = Truck::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['transporterProfile.user:id,name', 'truckType:id,name']))
            ->columns([
                TextColumn::make('plate_number')
                    ->searchable(),
                TextColumn::make('transporterProfile.user.name')
                    ->searchable(),
                TextColumn::make('truckType.name'),
                TextColumn::make('capacity_kg')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('availability')
                    ->badge(),
                TextColumn::make('insurance_expires_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('availability')
                    ->options(TruckAvailability::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrucks::route('/'),
        ];
    }
}
