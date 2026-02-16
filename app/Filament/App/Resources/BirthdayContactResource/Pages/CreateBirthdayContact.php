<?php

namespace App\Filament\App\Resources\BirthdayContactResource\Pages;

use App\Filament\App\Resources\BirthdayContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBirthdayContact extends CreateRecord
{
    protected static string $resource = BirthdayContactResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
