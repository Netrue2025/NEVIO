<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppSettingResource\Pages;
use App\Models\AppSetting;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;

class AppSettingResource extends Resource
{
    protected static ?string $model = AppSetting::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('sms_price_per_message')
                    ->label('Price per SMS (NGN)')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                      Forms\Components\TextInput::make('whatsapp_message_cost')
                    ->label('Price per WhatsApp Message (NGN)')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                Forms\Components\TextInput::make('currency')
                    ->default('NGN')
                    ->maxLength(10),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sms_price_per_message')
                    ->label('Price per SMS (NGN)'),
                Tables\Columns\TextColumn::make('whatsapp_message_cost')
                    ->label('Price per WhatsApp Message (NGN)'),
                Tables\Columns\TextColumn::make('currency')
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn () => ! AppSetting::query()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAppSettings::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'App settings';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-adjustments-vertical';
    }
}


