<?php

namespace App\Filament\Imports;

use App\Models\ContactEmail;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class ContactEmailImporter extends Importer
{
    protected static ?string $model = ContactEmail::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->rules(['max:255']),
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:255']),
        ];
    }

    protected function beforeSave(): void
    {
        $this->record->user_id = Auth::id();
    }

    public function resolveRecord(): ContactEmail
    {
        return new ContactEmail();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your contact email import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
