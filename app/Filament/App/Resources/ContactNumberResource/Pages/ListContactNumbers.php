<?php

namespace App\Filament\App\Resources\ContactNumberResource\Pages;

use App\Filament\App\Resources\ContactNumberResource;
use App\Filament\Imports\ContactNumberImporter;
use App\Models\AppSetting;
use App\Models\ContactNumber;
use App\Services\SmsService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListContactNumbers extends ListRecords
{
    protected static string $resource = ContactNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendSmsToAll')
                ->label('Send SMS to All')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Placeholder::make('disclaimer')
                        ->label('')
                        ->content('⚠️ This will send an SMS to ALL contacts in your numbers list. Use the bulk action below if you want to select specific contacts. SMS charges will apply based on your wallet balance.')
                        ->extraAttributes(['class' => 'text-warning-600 dark:text-warning-400 font-medium']),
                    Forms\Components\Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->rows(4),
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
                    $settings = $user->settings;

                    if (!SmsService::hasValidSenderSettings($settings)) {
                        Notification::make()
                            ->title('Sender settings not configured')
                            ->body('Please configure all sender phone number in Sender Settings before sending SMS.')
                            ->danger()
                            ->send();

                        return;
                    }
                    $appSetting = AppSetting::query()->first();
                    $pricePerMessage = $appSetting?->sms_price_per_message ?? 0;

                    if ($pricePerMessage === null) {
                        Notification::make()
                            ->title('SMS price not configured')
                            ->body('Contact admin to set SMS price before sending messages.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $wallet = $user->wallet;

                    if (! $wallet || $wallet->balance <= 0) {
                        Notification::make()
                            ->title('Insufficient wallet balance')
                            ->body('Fund your wallet before sending SMS.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $contacts = ContactNumber::where('user_id', $user->id)->get();
                    $totalRecipients = $contacts->count();

                    if ($totalRecipients === 0) {
                        Notification::make()
                            ->title('No contacts found')
                            ->body('You don\'t have any phone contacts to send to.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $totalCost = $totalRecipients * $pricePerMessage;

                    if ($wallet->balance < $totalCost) {
                        $maxRecipients = (int) floor($wallet->balance / $pricePerMessage);

                        if ($maxRecipients < 1) {
                            Notification::make()
                                ->title('Insufficient balance')
                                ->body("You don't have enough balance to send a single SMS. Please fund your wallet.")
                                ->danger()
                                ->send();

                            return;
                        }

                        $contacts = $contacts->take($maxRecipients);
                        $totalRecipients = $contacts->count();
                        $totalCost = $totalRecipients * $pricePerMessage;

                        Notification::make()
                            ->title('Partial send')
                            ->body("Insufficient balance to send to all contacts. Sent to {$totalRecipients} contacts based on your balance.")
                            ->warning()
                            ->send();
                    }

                    $smsService = app(SmsService::class);
                    $sent = 0;
                    $failed = 0;

                    foreach ($contacts as $contact) {
                        /** @var \App\Models\ContactNumber $contact */
                        try {
                            $provider = SmsService::determineProvider($contact->phone_number);
                            $from = SmsService::determineFrom($contact->phone_number);
                            $smsService->send(
                                $contact->phone_number,
                                $data['message'],
                                $provider,
                                $from,
                                $user->id,
                                $contact->id,
                                $pricePerMessage
                            );
                            $sent++;
                        } catch (\Exception $e) {
                            $failed++;
                            // Continue with next contact even if one fails
                        }
                    }

                    // Only debit if we actually sent messages
                    if ($sent > 0) {
                        $actualCost = $sent * $pricePerMessage;
                        $wallet->decrement('balance', $actualCost);
                        $wallet->transactions()->create([
                            'amount' => $actualCost,
                            'type' => 'debit',
                            'status' => 'success',
                            'description' => "SMS sending to {$sent} recipients (all contacts)",
                        ]);
                    }

                    $message = "Successfully sent {$sent} SMS";
                    if ($failed > 0) {
                        $message .= ", {$failed} failed";
                    }

                    Notification::make()
                        ->title('SMS processed')
                        ->body($message)
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('Add Contact Number'),
            ImportAction::make()
                ->importer(ContactNumberImporter::class)->label('Import')->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}
