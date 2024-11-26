<?php

namespace App\Filament\Resources\BoxResource\Pages;

use App\Filament\Resources\BoxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Box;

class ListBoxes extends ListRecords
{
    protected static string $resource = BoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->prefix(
                        fn(Box $record): ?string => $record->target ? 'Rp ' : null
                    )
                    ->numeric(thousandsSeparator: '.')
                    ->getStateUsing(
                        fn(Box $record): int|string => $record->target ?? '-'
                    ),
                Tables\Columns\TextColumn::make('balance')
                    ->sortable()
                    ->prefix('Rp ')
                    ->numeric(thousandsSeparator: '.'),
                Tables\Columns\TextColumn::make('progress')
                    ->sortable()
                    ->badge(fn(Box $record): bool => (bool) $record->target)
                    ->color('danger')
                    ->state(function (Box $record): string {
                        return $record->target
                            ? $record->balance / $record->target * 100 . '%'
                            : '-';
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
