<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\BirthdayContactResource\Pages;
use App\Filament\App\Pages\WhatsAppSubscription;
use App\Models\BirthdayContact;
use App\Services\BirthdayImageGenerator;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Filament\Actions\Action as ActionsAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BirthdayContactResource extends Resource
{
    protected static ?string $model = BirthdayContact::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Contact Name'),
                
                Forms\Components\FileUpload::make('photo_path')
                    ->label('Person Photo')
                    ->image()
                    ->directory('birthday-photos')
                    ->imageEditor()
                    ->required(),
                
                Forms\Components\DatePicker::make('birthday')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->label('Birthday Date'),
                
  
                Forms\Components\TextInput::make('phone')
                    ->label('WhatsApp Number')
                    ->tel()
                    ->helperText('Format: 2348012345678 (no + or spaces)'),
                
                Forms\Components\TextInput::make('whatsapp_group_id')
                    ->label('WhatsApp Group ID'),
                
                Forms\Components\TextInput::make('email')
                    ->email(),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function getNavigationLabel(): string
    {
        return 'Birthday Contacts';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Birthday Greetings';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-cake';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('user_id', Auth::id());
            })
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('birthday')
                    ->date('d M')
                    ->sortable()
                    ->label('Birthday'),
              
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('sendBirthdayWishes')
                        ->label('Send Birthday Wishes')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                            ->action(function (\Illuminate\Support\Collection $records) {
                                $whatsappService = new WhatsAppService();
                                $imageGenerator = new BirthdayImageGenerator();

                                $sent = 0;
                                $failed = 0;

                                $ids = $records->pluck('id')->toArray();
                                $contacts = \App\Models\BirthdayContact::whereIn('id', $ids)->with('user')->get();

                                foreach ($contacts as $contact) {
                                    try {
                                        $imagePath = $imageGenerator->generate($contact);

                                        if ($contact->user->is_whatsapp_subscribed) {
                                            try {
                                                if ($contact->phone) {
                                                    $mediaId = $whatsappService->uploadImage($imagePath);
                                                    $whatsappService->sendImageMessage(
                                                        $contact->phone,
                                                        $mediaId,
                                                        "Happy Birthday, {$contact->name}! 🎂"
                                                    );
                                                }

                                                if ($contact->whatsapp_group_id) {
                                                    $whatsappService->sendImageMessage(
                                                        $contact->whatsapp_group_id,
                                                        $mediaId,
                                                        "Happy Birthday, {$contact->name}! 🎂"
                                                    );
                                                }

                                                $sent++;
                                            } catch (\Exception $e) {
                                                $failed++;
                                            }
                                        }

                                        if ($contact->email) {
                                            try {
                                                Mail::send([], [], function ($message) use ($contact, $imagePath) {
                                                    $message->to($contact->email)
                                                        ->subject("🎉 Happy Birthday {$contact->name}!")
                                                        ->attach($imagePath)
                                                        ->html("Happy Birthday, {$contact->name}! 🎂 Wishing you joy and prosperity.");
                                                });
                                                $sent++;
                                            } catch (\Exception $e) {
                                                $failed++;
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        $failed++;
                                    }
                                }

                                $title = 'Birthday Wishes Processed';
                                $body = "Processed: " . ($sent + $failed) . ". Sent: {$sent}. Failed: {$failed}.";

                                Notification::make()
                                    ->success()
                                    ->title($title)
                                    ->body($body)
                                    ->send();
                            }),
                ]),
            ])
            ->headerActions([
                ActionsAction::make('enableWhatsApp')
                    ->label('Enable WhatsApp Sending')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn () => !Auth::user()->is_whatsapp_subscribed)
                    ->url(fn () => WhatsAppSubscription::getUrl()),

                ActionsAction::make('viewTemplate')
                    ->label('View Template')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->action(function () {
                        $templatePath = config('birthday-template.template_path');

                        if (file_exists($templatePath)) {
                            return redirect()->to('/storage/templates/birthday-template.png');
                        }

                        Notification::make()
                            ->danger()
                            ->title('Template Not Found')
                            ->body('Upload birthday-template.png to storage/app/public/templates/')
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBirthdayContacts::route('/'),
            'create' => Pages\CreateBirthdayContact::route('/create'),
            'edit' => Pages\EditBirthdayContact::route('/{record}/edit'),
        ];
    }
}
