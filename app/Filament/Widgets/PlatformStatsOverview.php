<?php

namespace App\Filament\Widgets;

use App\Models\EmailMessage;
use App\Models\SmsMessage;
use App\Models\Wallet;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{

    protected function getStats(): array
    {
        $totalRevenue = Wallet::query()->sum('balance');
        $totalSms = SmsMessage::query()->count();
        $totalEmail = EmailMessage::query()->count();

        return [
            Stat::make('Total Revenue (Wallets)', '₦' . number_format($totalRevenue, 2))
                ->icon('heroicon-o-banknotes')
                ->color(Color::Emerald),
            Stat::make('Total SMS Sent', (string) $totalSms)
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color(Color::Sky),
            Stat::make('Total Emails Sent', (string) $totalEmail)
                ->icon('heroicon-o-envelope-open')
                ->color(Color::Indigo),
        ];
    }
}


