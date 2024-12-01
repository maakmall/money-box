<?php

namespace App\Filament\Resources\BoxResource\Pages;

use App\Filament\Resources\BoxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Box;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

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
                    ->color(fn(string $state): string => match (true) {
                        (int) substr($state, 0, -1) >= 100 => 'success',
                        (int) substr($state, 0, -1) >= 75 => 'info',
                        (int) substr($state, 0, -1) >= 50 => 'gray',
                        (int) substr($state, 0, -1) >= 25 => 'warning',
                        default => 'danger',
                    })
                    ->state(function (Box $record): string {
                        return $record->target
                            ? number_format($record->balance / $record->target * 100, 2) . '%'
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\DeleteAction::make()
                        ->modalDescription(new HtmlString(
                            'Are you sure you would like to do this?
                            <br/>
                            <strong>
                            Make sure the balance in it is empty or moved to another box
                            </strong>
                            '
                        ))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Box Deleted')
                                ->body('The box has been deleted successfully')
                        ),
                    Tables\Actions\ForceDeleteAction::make()
                        ->modalDescription(new HtmlString(
                            'Are you sure you would like to do this?
                            <br/>
                            <strong>
                            This will delete your box and transaction history permanently
                            </strong>
                            '
                        ))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Box Deleted')
                                ->body('The box has been deleted permanently')
                        ),
                    Tables\Actions\RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Box Restored')
                                ->body('The box has been restored successfully')
                        )
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalDescription(new HtmlString(
                            'Are you sure you would like to do this?
                            <br/>
                            <strong>
                            Make sure the balance in it is empty or moved to another box
                            </strong>
                            '
                        ))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Box Deleted')
                                ->body('The box has been deleted successfully')
                        ),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->modalDescription(new HtmlString(
                            'Are you sure you would like to do this?
                            <br/>
                            <strong>
                            This will delete your box and transaction history permanently
                            </strong>
                            '
                        ))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Box Deleted')
                                ->body('The box has been deleted permanently')
                        ),
                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Box Restored')
                                ->body('The box has been restored successfully')
                        )
                ]),
            ]);
    }
}
