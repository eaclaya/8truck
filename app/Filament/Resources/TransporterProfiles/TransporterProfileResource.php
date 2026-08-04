<?php

namespace App\Filament\Resources\TransporterProfiles;

use App\Filament\Resources\TransporterProfiles\Pages\EditTransporterProfile;
use App\Filament\Resources\TransporterProfiles\Pages\ListTransporterProfiles;
use App\Models\TransporterProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TransporterProfileResource extends Resource
{
    protected static ?string $model = TransporterProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone')
                    ->required(),
                TextInput::make('driver_license_number'),
                TextInput::make('national_id'),
                TextInput::make('years_of_experience')
                    ->numeric()
                    ->minValue(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('phone'),
                TextColumn::make('driver_license_number')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('national_id')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('years_of_experience')
                    ->sortable(),
                TextColumn::make('rating_avg')
                    ->sortable(),
                TextColumn::make('rating_count')
                    ->sortable(),
                IconColumn::make('verified_at')
                    ->boolean()
                    ->getStateUsing(fn (TransporterProfile $record): bool => $record->isVerified()),
            ])
            ->filters([
                TernaryFilter::make('verified_at')
                    ->nullable(),
            ])
            ->recordActions([
                Action::make('verify')
                    ->icon(Heroicon::CheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (TransporterProfile $record): bool => ! $record->isVerified())
                    ->action(fn (TransporterProfile $record) => $record->forceFill(['verified_at' => now()])->save()),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransporterProfiles::route('/'),
            'edit' => EditTransporterProfile::route('/{record}/edit'),
        ];
    }
}
