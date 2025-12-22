<?php

namespace App\Filament\App\Resources\EmailMessageResource\Pages;

use App\Filament\App\Resources\EmailMessageResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewEmailMessage extends ViewRecord
{
    protected static string $resource = EmailMessageResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('from'),
                TextEntry::make('to'),
                TextEntry::make('subject'),
                TextEntry::make('status'),
                TextEntry::make('sent_at')
                    ->dateTime(),
                TextEntry::make('body')
                    ->html()
                    ->columnSpanFull(),
                TextEntry::make('error_message')
                    ->columnSpanFull()
                    ->visible(fn ($record) => filled($record->error_message)),
            ]);
    }
}


