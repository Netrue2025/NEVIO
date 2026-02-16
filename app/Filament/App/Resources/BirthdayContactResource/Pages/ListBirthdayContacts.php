<?php

namespace App\Filament\App\Resources\BirthdayContactResource\Pages;

use App\Filament\App\Resources\BirthdayContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBirthdayContacts extends ListRecords
{
    protected static string $resource = BirthdayContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
