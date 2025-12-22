<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\UserSettingResource\Pages;
use App\Models\UserSetting;
use Filament\Forms;
use Filament\Actions\EditAction as ActionsEditAction;
use Filament\Actions\CreateAction as ActionsCreateAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;  
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserSettingResource extends Resource
{
    protected static ?string $model = UserSetting::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('from_email')
                    ->label('From Email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('from_phone')
                    ->label('From Phone Number')
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
                Tables\Columns\TextColumn::make('from_email')
                    ->label('From Email'),
                Tables\Columns\TextColumn::make('from_phone')
                    ->label('From Phone'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Last updated'),
            ])
            ->actions([         
                ActionsEditAction::make(),
            ])
            ->headerActions([
                ActionsCreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();

                        return $data;
                    })
                    ->visible(fn () => ! UserSetting::where('user_id', Auth::id())->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUserSettings::route('/'),
        ];
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


