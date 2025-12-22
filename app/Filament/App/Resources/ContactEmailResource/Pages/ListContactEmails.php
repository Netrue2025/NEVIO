<?php

namespace App\Filament\App\Resources\ContactEmailResource\Pages;

use App\Filament\App\Resources\ContactEmailResource;
use App\Filament\Imports\ContactEmailImporter;
use App\Models\ContactEmail;
use App\Services\EmailService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListContactEmails extends ListRecords
{
    protected static string $resource = ContactEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendEmailToAll')
                ->label('Send Email to All')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Placeholder::make('disclaimer')
                        ->label('')
                        ->content('⚠️ This will send an email to ALL contacts in your email list. Use the bulk action below if you want to select specific contacts.')
                        ->extraAttributes(['class' => 'text-warning-600 dark:text-warning-400 font-medium']),
                    Forms\Components\TextInput::make('subject')
                        ->required()
                        ->maxLength(255)
                        ->label('Subject'),
                    Forms\Components\Textarea::make('body')
                        ->label('Message')
                        ->required()
                        ->rows(6),
                ])
                ->action(function (array $data) {
                    /** @var \App\Models\User|null $user */
                    $user = Auth::user();

                    if (! $user) {
                        Notification::make()
                            ->title('Not authenticated')
                            ->danger()
                            ->send();

                        return;
                    }

                    $from = optional($user->settings)->from_email ?? config('mail.from.address');
                    $contacts = ContactEmail::where('user_id', $user->id)->get();
                    $count = $contacts->count();

                    if ($count === 0) {
                        Notification::make()
                            ->title('No contacts found')
                            ->body('You don\'t have any email contacts to send to.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $emailService = app(EmailService::class);
                    $recipients = $contacts->map(fn($contact) => [
                        'to' => $contact->email,
                        'contact_email_id' => $contact->id,
                    ])->toArray();

                    $result = $emailService->sendBulk(
                        $recipients,
                        $data['subject'],
                        $data['body'],
                        $from,
                        $user->id
                    );

                    $message = "Successfully sent {$result['sent']} email(s)";
                    if ($result['failed'] > 0) {
                        $message .= ", {$result['failed']} failed";
                    }

                    Notification::make()
                        ->title('Emails processed')
                        ->body($message)
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('Add Contact Email'),
            ImportAction::make()
                ->importer(ContactEmailImporter::class)->label('Import')->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}
