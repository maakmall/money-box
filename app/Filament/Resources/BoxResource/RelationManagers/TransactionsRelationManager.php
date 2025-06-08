<?php

namespace App\Filament\Resources\BoxResource\RelationManagers;

use App\Enums\TransactionType;
use App\Filament\Resources\BoxResource\Pages\ViewBox;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('description')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(TransactionType $state): string => match ($state) {
                        TransactionType::Debit => 'info',
                        TransactionType::Kredit => 'danger',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->sortable()
                    ->prefix(function (Transaction $record): string {
                        return $record->type === TransactionType::Debit ? '+Rp ' : '-Rp ';
                    })
                    ->numeric(thousandsSeparator: '.'),
                Tables\Columns\TextColumn::make('balance')
                    ->prefix('Rp ')
                    ->numeric(thousandsSeparator: '.'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(TransactionType::forSelect())
                    ->columnSpanFull(),
                Tables\Filters\Filter::make('date_range')
                    ->columnSpan(2)
                    ->form([
                        Forms\Components\Fieldset::make('Date Range')
                            ->schema([
                                Forms\Components\DateTimePicker::make('start_from')
                                    ->native(false),
                                Forms\Components\DateTimePicker::make('until')
                                    ->native(false)
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $query->when($data['start_from'], function (Builder $query, $startFrom) {
                            $startFrom = Carbon::parse($startFrom)->toDateTimeString();

                            return $query->where('created_at', '>=', $startFrom);
                        });

                        $query->when($data['until'], function (Builder $query, $until) {
                            $until = Carbon::parse($until)->toDateTimeString();

                            return $query->where('created_at', '<=', $until);
                        });

                        return $query;
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];

                        if ($data['start_from']) {
                            $indicators[] = Indicator::make(
                                'From: ' . Carbon::parse($data['start_from'])->toDayDateTimeString()
                            )->removeField('start_from');
                        }

                        if ($data['until']) {
                            $indicators[] = Indicator::make(
                                'Until: ' . Carbon::parse($data['until'])->toDayDateTimeString()
                            )->removeField('until');
                        }

                        return $indicators;
                    }),
                Tables\Filters\Filter::make('amount_range')
                    ->columnSpan(2)
                    ->form([
                        Forms\Components\Fieldset::make('Amount Range')
                            ->schema([
                                Forms\Components\TextInput::make('start_from')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->mask(RawJs::make("\$money(\$input, ',', '.')")),
                                Forms\Components\TextInput::make('until')
                                    ->numeric()
                                    ->prefix('Rp ')
                                    ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $query->when($data['start_from'], function (Builder $query, $startFrom) {
                            return $query->where('amount', '>=', str_replace('.', '', $startFrom));
                        });

                        $query->when($data['until'], function (Builder $query, $until) {
                            return $query->where('amount', '<=', str_replace('.', '', $until));
                        });

                        return $query;
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];

                        if ($data['start_from']) {
                            $indicators[] = Indicator::make("From: {$data['start_from']}")
                                ->removeField('start_from');
                        }

                        if ($data['until']) {
                            $indicators[] = Indicator::make("Until: {$data['until']}")
                                ->removeField('until');
                        }

                        return $indicators;
                    })
            ], FiltersLayout::AboveContentCollapsible)
            ->deferFilters()
            ->headerActions([
                Tables\Actions\Action::make(TransactionType::Debit->name)
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp ')
                            ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                            ->stripCharacters('.'),
                        Forms\Components\Textarea::make('description'),
                    ])
                    ->modalWidth(MaxWidth::Small)
                    ->action(fn(array $data) => $this->debit($data)),
                Tables\Actions\Action::make(TransactionType::Kredit->name)
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp ')
                            ->mask(RawJs::make("\$money(\$input, ',', '.')"))
                            ->stripCharacters('.'),
                        Forms\Components\Textarea::make('description'),
                    ])
                    ->modalWidth(MaxWidth::Small)
                    ->action(fn(array $data) => $this->kredit($data)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewBox::class;
    }

    public function kredit(array $data): void
    {
        DB::transaction(function () use ($data) {
            $box = $this->getOwnerRecord();
            $box->decrement('balance', $data['amount']);

            $box->transactions()->create([
                'type' => TransactionType::Kredit,
                'amount' => $data['amount'],
                'description' => $data['description'],
                'balance' => $box->balance,
                'created_at' => now()
            ]);

            $this->notifyCreated()->send();
        });
    }

    public function debit(array $data): void
    {
        DB::transaction(function () use ($data) {
            $box = $this->getOwnerRecord();
            $box->increment('balance', $data['amount']);

            $box->transactions()->create([
                'type' => TransactionType::Debit,
                'amount' => $data['amount'],
                'description' => $data['description'],
                'balance' => $box->balance,
                'created_at' => now()
            ]);

            $this->notifyCreated()->send();
        });
    }

    public function notifyCreated(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Transaction Created')
            ->body('Transaction has been created successfully');
    }
}
