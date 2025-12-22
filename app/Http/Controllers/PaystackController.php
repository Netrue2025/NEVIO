<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use App\Services\PaystackService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    /**
     * Handle Paystack callback
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->url('/user/wallets');
        }

        try {
            $paystackService = app(PaystackService::class);
            $verification = $paystackService->verifyTransaction($reference);

            $transaction = WalletTransaction::where('reference', $reference)->first();

            if (!$transaction) {
                Log::error("Wallet transaction not found for reference: {$reference}");
                return redirect()->url('/user/wallets');
            }

            $status = $verification['data']['status'];
            $gatewayResponse = $verification['data']['gateway_response'];

            if ($status === 'success') {
                // Payment successful
                $transaction->update([
                    'status' => 'success',
                    'meta' => array_merge($transaction->meta ?? [], [
                        'paystack_data' => $verification['data'],
                        'verified_at' => now()->toDateTimeString(),
                    ]),
                ]);

                // Credit wallet
                $wallet = $transaction->wallet;
                $wallet->increment('balance', $transaction->amount);

                // Clear any existing error messages
                session()->forget('paystack_error');

                // Store success message in session for Filament notification
                session()->flash('paystack_success', "Your wallet has been credited with ₦" . number_format($transaction->amount, 2));

                return redirect()->to('/user/wallets');
            } else {
                // Payment failed
                $transaction->update([
                    'status' => 'failed',
                    'meta' => array_merge($transaction->meta ?? [], [
                        'paystack_data' => $verification['data'],
                        'gateway_response' => $gatewayResponse,
                        'verified_at' => now()->toDateTimeString(),
                    ]),
                ]);

                // Clear any existing success messages
                session()->forget('paystack_success');

                // Store error message in session
                session()->flash('paystack_error', 'Payment failed: ' . $gatewayResponse);

                return redirect()->to('/user/wallets');
            }
        } catch (\Exception $e) {
            Log::error("Paystack callback error: " . $e->getMessage(), [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            // Update transaction if it exists
            $transaction = WalletTransaction::where('reference', $reference)->first();
            if ($transaction && $transaction->status === 'pending') {
                $transaction->update([
                    'status' => 'failed',
                    'meta' => array_merge($transaction->meta ?? [], [
                        'error' => $e->getMessage(),
                        'verified_at' => now()->toDateTimeString(),
                    ]),
                ]);
            }

            session()->flash('paystack_error', 'Payment verification failed. Please contact support.');

            return redirect()->to('/user/wallets');
        }
    }

    /**
     * Handle Paystack webhook
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        $event = $payload['event'] ?? null;

        Log::info('Paystack webhook received', [
            'event' => $event,
            'data' => $payload['data'] ?? null,
        ]);

        if ($event === 'charge.success') {
            $reference = $payload['data']['reference'] ?? null;

            if ($reference) {
                try {
                    $paystackService = app(PaystackService::class);
                    $verification = $paystackService->verifyTransaction($reference);

                    $transaction = WalletTransaction::where('reference', $reference)->first();

                    if ($transaction && $transaction->status === 'pending') {
                        $status = $verification['data']['status'] ?? 'failed';

                        if ($status === 'success' && $verification['data']['amount'] / 100 == $transaction->amount) {
                            $transaction->update([
                                'status' => 'success',
                                'meta' => array_merge($transaction->meta ?? [], [
                                    'paystack_data' => $verification['data'],
                                    'webhook_processed_at' => now()->toDateTimeString(),
                                ]),
                            ]);

                            // Credit wallet
                            $wallet = $transaction->wallet;
                            $wallet->increment('balance', $transaction->amount);
                        } else {
                            $transaction->update([
                                'status' => 'failed',
                                'meta' => array_merge($transaction->meta ?? [], [
                                    'paystack_data' => $verification['data'],
                                    'webhook_processed_at' => now()->toDateTimeString(),
                                ]),
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Paystack webhook processing error: " . $e->getMessage(), [
                        'reference' => $reference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
