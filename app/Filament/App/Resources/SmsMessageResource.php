<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SmsMessageResource\Pages;
use App\Models\SmsMessage;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\ViewAction as ActionsViewAction;
use Filament\Tables\Table;  
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SmsMessageResource extends Resource
{
    protected static ?string $model = SmsMessage::class;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('user_id', Auth::id());
            })
            ->columns([
                Tables\Columns\TextColumn::make('to')
                    ->label('To')
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Cost (NGN)')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime()
                    ->label('Sent at'),
            ])
            ->filters([])
            ->actions([
                ActionsViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsMessages::route('/'),
            'view' => Pages\ViewSmsMessage::route('/{record}'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'SMS Logs';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Messages';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chat-bubble-left-right';
    }
}


