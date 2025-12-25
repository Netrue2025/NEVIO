<?php

namespace App\Filament\Pages;

use App\Models\UserSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserSenderSettings extends Page
{
    protected string $view = 'filament.pages.user-sender-settings';

    public ?array $data = [];

    public ?UserSetting $record = null;
    public function mount(): void
    {
        $this->record = UserSetting::firstOrCreate(
            ['user_id' => Auth::id()]
        );

        $this->form->fill($this->record->toArray());
    }
    public static function canAccess(): bool
    {
        return Auth::check();
    }
    public static function form(Schema $schema): Schema
    {
        return $schema->statePath('data')
            ->components([
                Section::make('Default Sender Settings')->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('from_email')
                            ->label('From Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('from_phone')->tel()
                            ->label('From Phone Number')->helperText('This is the default sender phone number used if no country-specific sender is set.')
                            ->maxLength(255)->dehydrateStateUsing(
                                fn($state) =>
                                $state ? preg_replace('/\s+/', '', $state) : null
                            ),
                    ]),
                ]),
                Section::make('Twilio Sender Settings')->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('twillo_uk_phone_from')->tel()
                            ->label('Twillo UK Phone From')->dehydrateStateUsing(
                                fn($state) =>
                                $state ? preg_replace('/\s+/', '', $state) : null
                            ),
                        Forms\Components\TextInput::make('twillo_us_phone_from')
                            ->label('Twillo US Phone From')->tel()->dehydrateStateUsing(
                                fn($state) =>
                                $state ? preg_replace('/\s+/', '', $state) : null
                            ),
                    ]),
                ]),
                Section::make('AfricaTalking Sender Settings')->schema([
                    Forms\Components\TextInput::make('africa_tallking_phone_from')->maxLength(255),
                ]),
            ]);
    }

    public function save(): void
    {
        $this->record->update($this->form->getState());

        Notification::make()
            ->title('Sender settings updated')
            ->success()
            ->send();
    }

    public static function getNavigationLabel(): string
    {
        return 'Sender Settings';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-cog-6-tooth';
    }
}
