<?php

namespace App\Filament\Resources\Shipments;

use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Models\Shipment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['customer:id,name', 'originCity:id,name', 'destinationCity:id,name', 'assignedTransporter.user:id,name'])
                ->withCount('quotes')
                ->latest())
            ->columns([
                TextColumn::make('reference')
                    ->limit(8)
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('originCity.name'),
                TextColumn::make('destinationCity.name'),
                TextColumn::make('pickup_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('quotes_count'),
                TextColumn::make('assignedTransporter.user.name')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ShipmentStatus::class),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Shipment $record): bool => $record->status->canTransitionTo(ShipmentStatus::Cancelled))
                    ->action(function (Shipment $record) {
                        app(TransitionShipmentStatusAction::class)->execute(
                            $record,
                            ShipmentStatus::Cancelled,
                            auth()->user(),
                            'Cancelled by administration',
                        );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShipments::route('/'),
        ];
    }
}
