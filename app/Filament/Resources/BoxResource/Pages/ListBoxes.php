<?php

namespace App\Filament\Resources\BoxResource\Pages;

use App\Filament\Resources\BoxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Box;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
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
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->weight(FontWeight::Bold)
                        ->size(TextColumnSize::Large)
                        ->description(
                            fn(Box $record): HtmlString => new HtmlString("$record->description<br><br>")
                        ),
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('balance')
                            ->sortable()
                            ->getStateUsing(function(Box $record): string {
                                $balance = number_format($record->balance, 0, ',', '.');
                                
                                if ($record->target) {
                                    $target = number_format($record->target, 0, ',', '.');

                                    return $balance . ' / ' . $target;
                                }

                                return $balance;
                            }),
                        Tables\Columns\TextColumn::make('progress')
                            ->sortable()
                            ->alignCenter()
                            ->badge(fn(Box $record): bool => (bool) $record->target)
                            ->color(fn(string $state): string => match (true) {
                                (int) substr($state, 0, -1) >= 100 => 'success',
                                (int) substr($state, 0, -1) >= 75 => 'info',
                                (int) substr($state, 0, -1) >= 50 => 'gray',
                                (int) substr($state, 0, -1) >= 25 => 'warning',
                                default => 'danger',
                            })
                            ->state(function (Box $record): ?string {
                                return $record->target
                                    ? number_format($record->balance / $record->target * 100, 2) . '%'
                                    : null;
                            }),
                    ]),
                ])
            ])
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make()
                //         ->modalDescription(new HtmlString(
                //             'Are you sure you would like to do this?
                //             <br/>
                //             <strong>
                //             Make sure the balance in it is empty or moved to another box
                //             </strong>
                //             '
                //         ))
                //         ->successNotification(
                //             Notification::make()
                //                 ->success()
                //                 ->title('Box Deleted')
                //                 ->body('The box has been deleted successfully')
                //         ),
                //     Tables\Actions\ForceDeleteBulkAction::make()
                //         ->modalDescription(new HtmlString(
                //             'Are you sure you would like to do this?
                //             <br/>
                //             <strong>
                //             This will delete your box and transaction history permanently
                //             </strong>
                //             '
                //         ))
                //         ->successNotification(
                //             Notification::make()
                //                 ->success()
                //                 ->title('Box Deleted')
                //                 ->body('The box has been deleted permanently')
                //         ),
                //     Tables\Actions\RestoreBulkAction::make()
                //         ->successNotification(
                //             Notification::make()
                //                 ->success()
                //                 ->title('Box Restored')
                //                 ->body('The box has been restored successfully')
                //         )
                // ]),
            ]);
    }
}
