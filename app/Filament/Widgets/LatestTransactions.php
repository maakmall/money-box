<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class LatestTransactions extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->whereRelation('box', 'user_id', Auth::id())
                    ->orderByDesc('created_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(TransactionType $state): string => match ($state) {
                        TransactionType::Debit => 'info',
                        TransactionType::Kredit => 'danger',
                    }),
                Tables\Columns\TextColumn::make('box.name')
                    ->label('Box'),
                Tables\Columns\TextColumn::make('amount')
                    ->sortable()
                    ->prefix(function (Transaction $record): string {
                        return $record->type === TransactionType::Debit ? '+Rp ' : '-Rp ';
                    })
                    ->numeric(thousandsSeparator: '.'),
                Tables\Columns\TextColumn::make('balance')
                    ->prefix('Rp ')
                    ->numeric(thousandsSeparator: '.'),
            ]);
    }
}
