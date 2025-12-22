<?php

namespace App\Filament\App\Resources\WalletResource\Pages;

use App\Filament\App\Resources\WalletResource;
use App\Filament\App\Widgets\WalletBalanceWidget;
use App\Models\WalletTransaction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ManageWallet extends ManageRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            WalletBalanceWidget::class
        ];
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        if ($user && $user->wallet) {
            return WalletTransaction::query()->where('wallet_id', $user->wallet->id);
        }
        return WalletTransaction::query()->whereRaw('1 = 0');
    }

    public function mount(): void
    {
        parent::mount();

        // Show notifications from Paystack callback
        if (session()->has('paystack_success')) {
            Notification::make()
                ->title('Payment Successful')
                ->body(session('paystack_success'))
                ->success()
                ->send();
            session()->forget('paystack_success');
        }

        if (session()->has('paystack_error')) {
            Notification::make()
                ->title('Payment Failed')
                ->body(session('paystack_error'))
                ->danger()
                ->send();
            session()->forget('paystack_error');
        }
    }
}


