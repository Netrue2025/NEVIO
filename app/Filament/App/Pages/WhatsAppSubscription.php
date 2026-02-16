<?php

namespace App\Filament\App\Pages;

use App\Models\AppSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WhatsAppSubscription extends Page
{
    protected string $view = 'filament.app.pages.whatsapp-subscription';

    protected static ?string $title = 'WhatsApp Subscription';

    public function mount()
    {
        if (Auth::user()->is_whatsapp_subscribed) {
            Notification::make()
                ->success()
                ->title('Already Subscribed')
                ->body('You already have WhatsApp sending enabled.')
                ->send();
        }
    }

    public function subscribe()
    {
        $appSetting = AppSetting::first();
        $cost = $appSetting?->whatsapp_message_cost ?? 50.00;

        $user = Auth::user();
        $wallet = $user->wallet;

        if (!$wallet || $wallet->balance < $cost) {
            Notification::make()
                ->danger()
                ->title('Insufficient Balance')
                ->body("You need ₦{$cost} to enable WhatsApp. Your balance: ₦" . ($wallet->balance ?? 0))
                ->send();
            return;
        }

        DB::transaction(function () use ($user, $wallet, $cost) {
            $wallet->decrement('balance', $cost);
            $wallet->transactions()->create([
                'amount' => $cost,
                'type' => 'debit',
                'status' => 'success',
                'description' => 'WhatsApp subscription activation',
            ]);
            $user->update(['is_whatsapp_subscribed' => true]);
        });

        Notification::make()
            ->success()
            ->title('WhatsApp Enabled!')
            ->body('You can now send birthday messages via WhatsApp.')
            ->send();

        return redirect(\App\Filament\App\Resources\BirthdayContactResource::getUrl('index'));
    }

    public function getWhatsAppCost()
    {
        $appSetting = AppSetting::first();
        return $appSetting?->whatsapp_message_cost ?? 50.00;
    }

    public function getWalletBalance()
    {
        $wallet = Auth::user()->wallet;
        return $wallet?->balance ?? 0;
    }

    public static function getNavigationLabel(): string
    {
        return 'WhatsApp Subscription';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Birthday Greetings';
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-credit-card';
    }
}
