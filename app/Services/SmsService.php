<?php

namespace App\Services;

use App\Models\SmsMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send SMS using the appropriate provider based on country
     *
     * @param string $to Phone number
     * @param string $message Message body
     * @param string $provider Provider name (africastalking, twilio, zenvia)
     * @param string|null $from Sender phone number
     * @param int|null $userId
     * @param int|null $contactNumberId
     * @param float $pricePerMessage
     * @return SmsMessage
     */
    public function send(
        string $to,
        string $message,
        string $provider,
        ?string $from = null,
        ?int $userId = null,
        ?int $contactNumberId = null,
        float $pricePerMessage = 0
    ): SmsMessage {
        // Create SMS message record
        $smsMessage = SmsMessage::create([
            'user_id' => $userId,
            'contact_number_id' => $contactNumberId,
            'from' => $from,
            'to' => $to,
            'body' => $message,
            'provider' => $provider,
            'units' => 1,
            'price_per_unit' => $pricePerMessage,
            'total_price' => $pricePerMessage,
            'status' => 'pending',
        ]);

        try {
            $result = match ($provider) {
                'africastalking' => $this->sendViaAfricasTalking($to, $message, $from),
                'twilio' => $this->sendViaTwilio($to, $message, $from),
                'zenvia' => $this->sendViaZenvia($to, $message, $from),
                default => throw new \Exception("Unknown SMS provider: {$provider}"),
            };

            // Update status to sent
            $smsMessage->update([
                'status' => 'sent',
                'provider_message_id' => $result['message_id'] ?? null,
                'sent_at' => now(),
            ]);

            Log::info("SMS sent successfully", [
                'sms_message_id' => $smsMessage->id,
                'to' => $to,
                'provider' => $provider,
            ]);
        } catch (\Exception $e) {
            // Update status to failed
            $smsMessage->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("SMS sending failed", [
                'sms_message_id' => $smsMessage->id,
                'to' => $to,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $smsMessage;
    }

    /**
     * Send SMS via Africa's Talking (Nigeria)
     */
    protected function sendViaAfricasTalking(string $to, string $message, ?string $from): array
    {
        $apiKey = config('services.africas_talking.api_key');
        $username = config('services.africas_talking.username');
        $senderId = config('services.africas_talking.sender_id', $from);

        if (!$apiKey || !$username) {
            throw new \Exception('Africa\'s Talking API credentials not configured');
        }

        $response = Http::withHeaders([
            'apiKey' => $apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()->post("https://api.africastalking.com/version1/messaging", [
            'username' => $username,
            'to' => $this->formatPhoneNumber($to),
            'message' => $message,
            'from' => $senderId,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Africa's Talking API error: " . $response->body());
        }

        $data = $response->json();
        $recipients = $data['SMSMessageData']['Recipients'] ?? [];
        
        if (empty($recipients) || $recipients[0]['statusCode'] !== '101') {
            throw new \Exception("Africa's Talking failed: " . ($recipients[0]['status'] ?? 'Unknown error'));
        }

        return [
            'message_id' => $recipients[0]['messageId'] ?? null,
        ];
    }

    /**
     * Send SMS via Twilio (UK and USA)
     */
    protected function sendViaTwilio(string $to, string $message, ?string $from): array
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $fromNumber = config('services.twilio.from_number', $from);

        if (!$accountSid || !$authToken || !$fromNumber) {
            throw new \Exception('Twilio API credentials not configured');
        }

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $fromNumber,
                'To' => $this->formatPhoneNumber($to),
                'Body' => $message,
            ]);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception("Twilio API error: " . ($error['message'] ?? $response->body()));
        }

        $data = $response->json();

        return [
            'message_id' => $data['sid'] ?? null,
        ];
    }

    /**
     * Send SMS via Zenvia (Brazil)
     */
    protected function sendViaZenvia(string $to, string $message, ?string $from): array
    {
        $apiKey = config('services.zenvia.api_key');
        $fromNumber = config('services.zenvia.from_number', $from);

        if (!$apiKey || !$fromNumber) {
            throw new \Exception('Zenvia API credentials not configured');
        }

        $response = Http::withHeaders([
            'X-API-TOKEN' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.zenvia.com/v2/channels/sms/messages', [
            'from' => $fromNumber,
            'to' => $this->formatPhoneNumber($to),
            'contents' => [
                [
                    'type' => 'text',
                    'text' => $message,
                ],
            ],
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception("Zenvia API error: " . ($error['message'] ?? $response->body()));
        }

        $data = $response->json();

        return [
            'message_id' => $data['id'] ?? null,
        ];
    }

    /**
     * Format phone number (remove spaces, dashes, etc.)
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Ensure it starts with + or country code
        if (!str_starts_with($phone, '+')) {
            // If it doesn't start with +, assume it needs country code
            // This is a basic implementation - you may need to adjust based on your needs
            $phone = '+' . ltrim($phone, '0');
        }

        return $phone;
    }

    /**
     * Determine provider based on country code or country name
     */
    public static function determineProvider(?string $countryCode = null, ?string $country = null): string
    {
        $countryCode = strtoupper((string) $countryCode);
        $country = strtoupper((string) $country);

        // Nigeria
        if ($countryCode === '+234' || $country === 'NG' || $country === 'NIGERIA' || $countryCode === '234') {
            return 'africastalking';
        }

        // UK and USA
        if (in_array($country, ['UK', 'GB', 'UNITED KINGDOM', 'US', 'USA', 'UNITED STATES'], true)) {
            return 'twilio';
        }

        // Brazil
        if (in_array($country, ['BR', 'BRAZIL'], true)) {
            return 'zenvia';
        }

        // Default to Twilio
        return 'twilio';
    }
}

