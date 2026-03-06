<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BirthdayContact;
use App\Services\WhatsAppService;
use App\Services\BirthdayImageGenerator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendBirthdayMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'birthday:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automatic birthday messages daily';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $contacts = BirthdayContact::where('is_active', true)
            ->whereMonth('birthday', $today->month)
            ->whereDay('birthday', $today->day)
            ->with('user')
            ->get();

        if ($contacts->isEmpty()) {
            $this->info('No birthdays today.');
            return 0;
        }

        $whatsappService = new WhatsAppService();
        $imageGenerator = new BirthdayImageGenerator();

        $sent = 0;
        $failed = 0;

        foreach ($contacts as $contact) {
            try {
                // Generate birthday image
                $imagePath = $imageGenerator->generate($contact);

                if ($contact->user->is_whatsapp_subscribed) {
                    try {
                        if ($contact->phone) {
                            $mediaId = $whatsappService->uploadImage($imagePath);
                            $whatsappService->sendImageMessage(
                                $contact->phone,
                                $mediaId,
                                $contact->birthday_message ?? "Happy Birthday, {$contact->name}! 🎂"
                            );
                        };

                        if ($contact->whatsapp_group_id) {
                            // This is a placeholder should be changed to fit group messaging on whatsapp
                            $whatsappService->sendImageMessage(
                                $contact->whatsapp_group_id,
                                $mediaId,
                                $contact->birthday_message ?? "Happy Birthday, {$contact->name}! 🎂"
                            );
                        }

                        $this->info("WhatsApp sent to {$contact->name}");
                        $sent++;
                    } catch (\Exception $e) {
                        $this->error("WhatsApp failed for {$contact->name}: " . $e->getMessage());
                        $failed++;
                    }
                } else {
                    $this->warn("{$contact->name} - WhatsApp not subscribed, sending email instead");
                    // Fall through to email
                }

                if ($contact->email) {
                    try {
                        Mail::send([], [], function ($message) use ($contact, $imagePath) {
                            $message->to($contact->email)
                                ->subject("🎉 Happy Birthday {$contact->name}!")
                                ->attach($imagePath)
                                ->html($contact->birthday_message ?? "Happy Birthday, {$contact->name}! 🎂 Wishing you joy and prosperity.");
                        });
                        $this->info("Email sent to {$contact->name}");
                        $sent++;
                    } catch (\Exception $e) {
                        $this->error("Email failed for {$contact->name}: " . $e->getMessage());
                        $failed++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("Failed for {$contact->name}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("Birthday messages processing completed. Sent: {$sent}, Failed: {$failed}");
        return 0;
    }
}
