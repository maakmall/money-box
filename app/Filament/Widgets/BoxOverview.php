<?php

namespace App\Filament\Widgets;

use App\Models\Box;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class BoxOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $box = Box::select('id', 'user_id', 'balance')->where('user_id', Auth::id());

        return [
            Stat::make('Box', $box->count())
                ->icon('heroicon-o-archive-box')
                ->description('Box total created by you'),
            Stat::make('Balance', 'Rp ' . number_format($box->sum('balance'), 0, ',', '.'))
                ->icon('heroicon-o-archive-box')
                ->description('Total balance in your boxes'),

        ];
    }

    public function getColumns(): int
    {
        return 2;
    }
}
