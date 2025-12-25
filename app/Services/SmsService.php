<?php

namespace App\Services;

use App\Models\SmsMessage;
use App\Models\UserSetting;
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
        $senderId = $from;

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
        $fromNumber = $from;

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
     * Format phone number (remove spaces, dashes, etc.)
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Ensure it starts with + or country code
        if (!str_starts_with($phone, '+')) {

            $phone = '+' . ltrim($phone, '0');
        }

        return $phone;
    }
    /**
     * Extract country code from an E.164 phone number
     * Example: +2348012345678 → 234
     */
    public static function extractCountryCode(string $phone): ?string
    {
        if (preg_match('/^\+(\d{1,3})/', $phone, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Determine SMS provider based on phone number
     */
    public static function determineProvider(string $phoneNumber): string
    {
        $countryCode = self::extractCountryCode($phoneNumber);

        if ($countryCode === '234') {
            return 'africastalking';
        }

        if (in_array($countryCode, ['44', '1'], true)) {
            return 'twilio';
        }

        return 'twilio';
    }

    /**
     * Determine sender ("from") number based on contact phone number
     */
    public static function determineFrom(
        ?\App\Models\UserSetting $settings,
        string $contactPhone
    ): ?string {
        if (! $settings) {
            return null;
        }

        $countryCode = self::extractCountryCode($contactPhone);

        return match ($countryCode) {
            '234' => $settings->africa_tallking_phone_from
                ?: $settings->from_phone,

            '44' => $settings->twillo_uk_phone_from
                ?: $settings->from_phone,

            '1' => $settings->twillo_us_phone_from
                ?: $settings->from_phone,

            default => $settings->from_phone,
        };
    }


    /**
     * Check if user has ALL sender settings configured
     */
    public static function hasValidSenderSettings(?UserSetting $settings): bool
    {
        if (! $settings) {
            return false;
        }

        return collect([
            $settings->from_phone,
            $settings->twillo_uk_phone_from,
            $settings->twillo_us_phone_from,
            $settings->africa_tallking_phone_from,
        ])
            ->every(fn($value) => filled($value));
    }
}
