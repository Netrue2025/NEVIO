<?php

namespace App\Filament\App\Resources\SmsMessageResource\Pages;

use App\Filament\App\Resources\SmsMessageResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewSmsMessage extends ViewRecord
{
    protected static string $resource = SmsMessageResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('from'),
                TextEntry::make('to'),
                TextEntry::make('provider'),
                TextEntry::make('status'),
                TextEntry::make('total_price')
                    ->label('Cost (NGN)'),
                TextEntry::make('sent_at')
                    ->dateTime(),
                TextEntry::make('body')
                    ->columnSpanFull(),
                TextEntry::make('error_message')
                    ->columnSpanFull()
                    ->visible(fn ($record) => filled($record->error_message)),
            ]);
    }
}


