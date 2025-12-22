<?php

namespace App\Filament\App\Resources\ContactNumberResource\Pages;

use App\Filament\App\Resources\ContactNumberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContactNumber extends CreateRecord
{
    protected static string $resource = ContactNumberResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}


