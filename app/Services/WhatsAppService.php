<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\AppSetting;

class WhatsAppService
{
    private string $token;
    private string $phoneNumberId;

    public function __construct()
    {
        $appSetting = AppSetting::first();
        $this->token = $appSetting?->whatsapp_token ?? env('WHATSAPP_TOKEN', '');
        $this->phoneNumberId = $appSetting?->whatsapp_phone_number_id ?? env('WHATSAPP_PHONE_NUMBER_ID', '');
    }

    public function uploadImage(string $imagePath): string
    {
        $response = Http::withToken($this->token)
            ->attach(
                'file',
                file_get_contents($imagePath),
                'birthday.png'
            )
            ->post(
                "https://graph.facebook.com/v19.0/{$this->phoneNumberId}/media",
                [
                    'messaging_product' => 'whatsapp',
                    'type' => 'image/png',
                ]
            );

        if (!$response->successful()) {
            throw new \Exception('WhatsApp media upload failed: ' . $response->body());
        }

        return $response->json('id');
    }

    public function sendTemplateMessage($contact, string $mediaId)
    {
        $response = Http::withToken($this->token)
            ->post(
                "https://graph.facebook.com/v19.0/{$this->phoneNumberId}/messages",
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $contact->phone,
                    'type' => 'template',
                    'template' => [
                        'name' => 'birthday_greeting_image',
                        'language' => ['code' => 'en_US'],
                        'components' => [
                            [
                                'type' => 'header',
                                'parameters' => [
                                    [
                                        'type' => 'image',
                                        'image' => ['id' => $mediaId],
                                    ],
                                ],
                            ],
                            [
                                'type' => 'body',
                                'parameters' => [
                                    [
                                        'type' => 'text',
                                        'text' => $contact->name,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );

        if (!$response->successful()) {
            throw new \Exception('WhatsApp send failed: ' . $response->body());
        }

        return $response->json();
    }

    public function sendImageMessage(string $phone, string $mediaId, string $caption = '')
    {
        $response = Http::withToken($this->token)
            ->post(
                "https://graph.facebook.com/v19.0/{$this->phoneNumberId}/messages",
                [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'image',
                    'image' => [
                        'id' => $mediaId,
                        'caption' => $caption,
                    ],
                ]
            );

        if (!$response->successful()) {
            throw new \Exception('WhatsApp send failed: ' . $response->body());
        }

        return $response->json();
    }
}
