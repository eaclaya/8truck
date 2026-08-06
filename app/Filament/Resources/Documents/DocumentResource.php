<?php

namespace App\Filament\Resources\Documents;

use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Models\Document;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['documentable', 'reviewer:id,name'])->latest())
            ->columns([
                TextColumn::make('documentable_type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('documentable_id'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('expires_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('reviewer.name'),
                TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DocumentStatus::class),
            ])
            ->recordActions([
                Action::make('open')
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->url(fn (Document $record): string => route('documents.download', $record))
                    ->openUrlInNewTab(),
                Action::make('approve')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Document $record): bool => $record->status === DocumentStatus::Pending)
                    ->action(fn (Document $record) => $record->forceFill([
                        'status' => DocumentStatus::Approved,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ])->save()),
                Action::make('reject')
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->visible(fn (Document $record): bool => $record->status === DocumentStatus::Pending)
                    ->schema([
                        Textarea::make('notes')
                            ->required(),
                    ])
                    ->action(fn (Document $record, array $data) => $record->forceFill([
                        'status' => DocumentStatus::Rejected,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                        'notes' => $data['notes'],
                    ])->save()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
        ];
    }
}
