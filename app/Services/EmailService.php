<?php

namespace App\Services;

use App\Models\EmailMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Send an email to a recipient
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string|null $from
     * @param int|null $userId
     * @param int|null $contactEmailId
     * @return EmailMessage
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        ?string $from = null,
        ?int $userId = null,
        ?int $contactEmailId = null
    ): EmailMessage {
        $from = $from ?? config('mail.from.address');
        $fromName = config('mail.from.name', 'SMS and Email App');

        // Create email message record
        $emailMessage = EmailMessage::create([
            'user_id' => $userId,
            'contact_email_id' => $contactEmailId,
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
        ]);

        try {
            // Send the email
            Mail::send('emails.custom', [
                'subject' => $subject,
                'body' => $body,
            ], function ($message) use ($to, $subject, $from, $fromName) {
                $message->to($to)
                    ->subject($subject)
                    ->from($from, $fromName);
            });

            // Update status to sent
            $emailMessage->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            // Update status to failed
            $emailMessage->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Email sending failed", [
                'email_message_id' => $emailMessage->id,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $emailMessage;
    }

    /**
     * Send bulk emails
     *
     * @param array $recipients Array of ['to' => email, 'contact_email_id' => id]
     * @param string $subject
     * @param string $body
     * @param string|null $from
     * @param int|null $userId
     * @return array ['sent' => count, 'failed' => count]
     */
    public function sendBulk(
        array $recipients,
        string $subject,
        string $body,
        ?string $from = null,
        ?int $userId = null
    ): array {
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                $this->send(
                    $recipient['to'],
                    $subject,
                    $body,
                    $from,
                    $userId,
                    $recipient['contact_email_id'] ?? null
                );
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                // Continue with next recipient even if one fails
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
        ];
    }
}

