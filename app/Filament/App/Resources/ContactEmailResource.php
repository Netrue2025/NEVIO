<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ContactEmailResource\Pages;
use App\Models\ContactEmail;
use App\Services\EmailService;
use Filament\Actions\BulkAction as ActionsBulkAction;
use Filament\Actions\EditAction as ActionsEditAction;
use Filament\Actions\DeleteAction as ActionsDeleteAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ContactEmailResource extends Resource
{
    protected static ?string $model = ContactEmail::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
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
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ActionsEditAction::make(),
                ActionsDeleteAction::make(),
            ])
            ->bulkActions([
                ActionsBulkAction::make('sendEmail')
                    ->label('Send Email to Selected')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content('This will send email to the selected contacts only. Use "Send Email to All" button above to send to all contacts.')
                            ->extraAttributes(['class' => 'text-gray-600 dark:text-gray-400']),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('body')
                            ->label('Message')
                            ->required()
                            ->rows(6),
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

                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('No contacts selected')
                                ->body('Please select at least one contact to send email to.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $from = optional($user->settings)->from_email ?? config('mail.from.address');
                        $emailService = app(EmailService::class);
                        
                        $recipients = $records->map(fn ($contact) => [
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
                ActionsBulkAction::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records) {
                        $filename = 'emails-export-' . now()->format('Y-m-d_H-i-s') . '.csv';

                        return response()->streamDownload(function () use ($records) {
                            $handle = fopen('php://output', 'wb');
                            fputcsv($handle, ['Name', 'Email', 'Created At']);

                            foreach ($records as $record) {
                                /** @var \App\Models\ContactEmail $record */
                                fputcsv($handle, [
                                    $record->name,
                                    $record->email,
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
            'index' => Pages\ListContactEmails::route('/'),
            'create' => Pages\CreateContactEmail::route('/create'),
            'edit' => Pages\EditContactEmail::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Emails';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Contacts';
    }
protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-envelope';
    }
}


