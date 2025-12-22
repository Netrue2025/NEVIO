<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('role')
                    ->badge(),
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('wallet_balance')
                    ->label('Wallet Balance (NGN)')
                    ->state(fn (User $record) => '₦' . number_format((float) ($record->wallet->balance ?? 0), 2)),
                TextEntry::make('total_sms_sent')
                    ->label('Total SMS Sent')
                    ->state(fn (User $record) => $record->smsMessages()->count()),
                TextEntry::make('total_email_sent')
                    ->label('Total Emails Sent')
                    ->state(fn (User $record) => $record->emailMessages()->count()),
                TextEntry::make('email_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
