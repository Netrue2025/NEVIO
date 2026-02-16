<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::card>
            <h2 class="text-2xl font-bold mb-4">Enable WhatsApp Birthday Sending</h2>
            
            <div class="mt-4 space-y-2">
                <p>Current Wallet Balance: <strong>₦{{ $this->getWalletBalance() }}</strong></p>
                <p>WhatsApp Subscription Cost: <strong>₦{{ $this->getWhatsAppCost() }}</strong></p>
            </div>

            @if(!auth()->user()->is_whatsapp_subscribed)
                <x-filament::button 
                    wire:click="subscribe" 
                    color="success"
                    class="mt-6"
                >
                    Subscribe Now
                </x-filament::button>
            @else
                <x-filament::badge color="success" class="mt-6 inline-block">
                    ✓ WhatsApp Enabled
                </x-filament::badge>
            @endif
        </x-filament::card>
    </div>
</x-filament-panels::page>
