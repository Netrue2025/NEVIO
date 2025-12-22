<?php

namespace App\Filament\App\Resources\ContactEmailResource\Pages;

use App\Filament\App\Resources\ContactEmailResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContactEmail extends CreateRecord
{
    protected static string $resource = ContactEmailResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}


