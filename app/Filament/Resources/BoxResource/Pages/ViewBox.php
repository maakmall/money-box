<?php

namespace App\Filament\Resources\BoxResource\Pages;

use App\Filament\Resources\BoxResource;
use App\Models\Box;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Support\Enums\FontWeight;

class ViewBox extends ViewRecord
{
    protected static string $resource = BoxResource::class;

    protected static ?string $title = 'Detail Box';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3)
            ->schema([
                Section::make('Balance')
                    ->description('Current balance of your box')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('balance')
                            ->numeric(thousandsSeparator: '.')
                            ->prefix('Rp. ')
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold),
                        TextEntry::make('progress')
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
                            ->state(
                                function (Box $record): string {
                                    return $record->target
                                        ? $record->balance / $record->target * 100 . '%'
                                        : '-';
                                }
                            )
                    ]),
                Section::make('Box')
                    ->description('Detailed information of your box')
                    ->columns()
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('target')
                            ->placeholder('-')
                            ->numeric(thousandsSeparator: '.')
                            ->prefix(
                                fn(Box $record): ?string => $record->target ? 'Rp ' : null
                            )
                            ->getStateUsing(
                                fn(Box $record): int|string => $record->target ?? '-'
                            ),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->hidden(fn(Box $record): bool => !$record->deleted_at),
                    ]),
            ]);
    }

    public function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->hidden(fn(Box $record): bool => (bool) $record->deleted_at),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
            Actions\ForceDeleteAction::make()
                ->icon('heroicon-o-trash'),
            Actions\RestoreAction::make()
                ->icon('heroicon-o-arrow-path')
        ];
    }
}
