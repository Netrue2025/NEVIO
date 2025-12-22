<?php

namespace App\Filament\App\Widgets;

use App\Models\ContactNumber;
use App\Models\EmailMessage;
use App\Models\SmsMessage;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UserStatsOverview extends StatsOverviewWidget
{

    protected function getStats(): array
    {
        $user = Auth::user();

        $walletBalance = $user?->wallet?->balance ?? 0;
        $smsCount = SmsMessage::query()->where('user_id', $user?->id)->count();
        $emailCount = EmailMessage::query()->where('user_id', $user?->id)->count();
        $smscontactCount = ContactNumber::query()->where('user_id', $user?->id)->count();
        return [
            Stat::make('Total SMS Contacts', (string) $smscontactCount)
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color(Color::Sky),
                
            Stat::make('Total SMS Sent', (string) $smsCount)
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color(Color::Sky),
            Stat::make('Total Emails Sent', (string) $emailCount)
                ->icon('heroicon-o-envelope-open')
                ->color(Color::Indigo),
        ];
    }
}
