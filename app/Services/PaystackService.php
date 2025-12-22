<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $secretKey;
    protected string $publicKey;
    protected string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->publicKey = config('services.paystack.public_key');
    }

    /**
     * Initialize a transaction
     *
     * @param float $amount Amount in Naira
     * @param string $email Customer email
     * @param string $reference Transaction reference
     * @param array $metadata Additional metadata
     * @return array
     */
    public function initializeTransaction(
        float $amount,
        string $email,
        string $reference,
        array $metadata = []
    ): array {
        $amountInKobo = (int) ($amount * 100); // Convert to kobo

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/transaction/initialize", [
            'email' => $email,
            'amount' => $amountInKobo,
            'reference' => $reference,
            'metadata' => $metadata,
            'callback_url' => route('paystack.callback'),
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception("Paystack API error: " . ($error['message'] ?? 'Unknown error'));
        }

        return $response->json();
    }

    /**
     * Verify a transaction
     *
     * @param string $reference Transaction reference
     * @return array
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception("Paystack API error: " . ($error['message'] ?? 'Unknown error'));
        }

        return $response->json();
    }

    /**
     * Get public key
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
}

