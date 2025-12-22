<?php

namespace App\Filament\App\Widgets;

use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class WalletBalanceWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $walletBalance = $user?->wallet?->balance ?? 0;

        return [
            Stat::make('Wallet Balance', '₦' . number_format($walletBalance, 2))
                ->icon('heroicon-o-wallet')
                ->color(Color::Emerald)
                ->description('Available balance for SMS sending'),
        ];
    }
}

