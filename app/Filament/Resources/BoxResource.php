<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoxResource\Pages;
use App\Filament\Resources\BoxResource\RelationManagers;
use App\Models\Box;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;

class BoxResource extends Resource
{
    protected static ?string $model = Box::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Box Information')
                    ->description('Describe the detailed information of your box')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(20)
                            ->unique(
                                'boxes',
                                'name',
                                ignoreRecord: true,
                                modifyRuleUsing: fn(Unique $rule): Unique =>
                                $rule->where('user_id', Auth::user()->id)
                            ),
                        Forms\Components\TextInput::make('target')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->nullable()
                            ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                            ->stripCharacters('.')
                            ->hintIcon(
                                'heroicon-o-question-mark-circle',
                                'What is the target balance of this box?'
                            ),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns()
            ]);
    }

    public static function table(Table $table): Table
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::user()->id)
            ->withTrashed();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBoxes::route('/'),
            'create' => Pages\CreateBox::route('/create'),
            'edit' => Pages\EditBox::route('/{record}/edit'),
            'view' => Pages\ViewBox::route('/{record}'),
        ];
    }
}
