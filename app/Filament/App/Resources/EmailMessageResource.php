<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\EmailMessageResource\Pages;
use App\Models\EmailMessage;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\ViewAction as ActionsViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EmailMessageResource extends Resource
{
    protected static ?string $model = EmailMessage::class;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('user_id', Auth::id())->orderBy('created_at', 'desc');
            })
            ->columns([
                Tables\Columns\TextColumn::make('to')
                    ->label('To')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
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
            'index' => Pages\ListEmailMessages::route('/'),
            'view' => Pages\ViewEmailMessage::route('/{record}'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Email Logs';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Messages';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-envelope-open';
    }
}


