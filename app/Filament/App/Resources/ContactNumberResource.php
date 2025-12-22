<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ContactNumberResource\Pages;
use App\Models\AppSetting;
use App\Models\ContactNumber;
use App\Services\SmsService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\BulkAction as ActionsBulkAction;
use Filament\Actions\EditAction as ActionsEditAction;
use Filament\Actions\DeleteAction as ActionsDeleteAction;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ContactNumberResource extends Resource
{
    protected static ?string $model = ContactNumber::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone_number')
                    ->label('Phone Number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('country_code')
                    ->maxLength(5)
                    ->placeholder('+234'),
                Forms\Components\TextInput::make('country')
                    ->maxLength(255)
                    ->placeholder('NG'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('user_id', Auth::id());
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country_code')
                    ->label('Code'),
                Tables\Columns\TextColumn::make('country'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                ActionsEditAction::make(),
                ActionsDeleteAction::make(),
            ])
            ->bulkActions([
                ActionsBulkAction::make('sendSms')
                    ->label('Send SMS to Selected')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content('This will send SMS to the selected contacts only. Use "Send SMS to All" button above to send to all contacts. SMS charges will apply based on your wallet balance.')
                            ->extraAttributes(['class' => 'text-gray-600 dark:text-gray-400']),
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (Collection $records, array $data) {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();

                        if (! $user) {
                            Notification::make()
                                ->title('Not authenticated')
                                ->danger()
                                ->send();

                            return;
                        }

                        $appSetting = AppSetting::query()->first();
                        $pricePerMessage = $appSetting?->sms_price_per_message ?? 0;

                        if ($pricePerMessage <= 0) {
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

                        $totalRecipients = $records->count();
                        $totalCost = $totalRecipients * $pricePerMessage;

                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('No contacts selected')
                                ->body('Please select at least one contact to send SMS to.')
                                ->warning()
                                ->send();

                            return;
                        }

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

                            $records = $records->take($maxRecipients);
                            $totalRecipients = $records->count();
                            $totalCost = $totalRecipients * $pricePerMessage;

                            Notification::make()
                                ->title('Partial send')
                                ->body("Insufficient balance to send to all selected numbers. Sent to {$totalRecipients} numbers based on your balance.")
                                ->warning()
                                ->send();
                        }

                        $smsService = app(SmsService::class);
                        $from = optional($user->settings)->from_phone;
                        $sent = 0;
                        $failed = 0;

                        foreach ($records as $contact) {
                            /** @var \App\Models\ContactNumber $contact */
                            try {
                                $provider = SmsService::determineProvider($contact->country_code, $contact->country);
                                
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
                                'description' => "SMS sending to {$sent} recipients",
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
                ActionsBulkAction::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records) {
                        $filename = 'numbers-export-' . now()->format('Y-m-d_H-i-s') . '.csv';

                        return response()->streamDownload(function () use ($records) {
                            $handle = fopen('php://output', 'wb');
                            fputcsv($handle, ['Name', 'Phone', 'Country Code', 'Country', 'Created At']);

                            foreach ($records as $record) {
                                /** @var \App\Models\ContactNumber $record */
                                fputcsv($handle, [
                                    $record->name,
                                    $record->phone_number,
                                    $record->country_code,
                                    $record->country,
                                    optional($record->created_at)->toDateTimeString(),
                                ]);
                            }

                            fclose($handle);
                        }, $filename, [
                            'Content-Type' => 'text/csv',
                        ]);
                    }),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactNumbers::route('/'),
            'create' => Pages\CreateContactNumber::route('/create'),
            'edit' => Pages\EditContactNumber::route('/{record}/edit'),
        ];
    }
protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
    public static function getNavigationLabel(): string
    {
        return 'Numbers';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Contacts';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-phone';
    }
}


