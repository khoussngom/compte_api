<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\MessageServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWelcomeNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $payload;


    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        // Try to send SMS via the bound MessageServiceInterface if available
        try {
            if (app()->bound(MessageServiceInterface::class)) {
                $service = app(MessageServiceInterface::class);
                if (!empty($this->payload['telephone']) && !empty($this->payload['message_sms'])) {
                    $service->sendMessage($this->payload['telephone'], $this->payload['message_sms']);
                }
            } else {
                Log::channel('comptes')->info('MessageServiceInterface not bound, skipping SMS send', ['payload' => $this->payload]);
            }
        } catch (\Throwable $e) {
            Log::channel('comptes')->error('Failed to send SMS welcome', ['error' => $e->getMessage(), 'payload' => $this->payload]);
        }

        try {
            if (!empty($this->payload['email'])) {
                $subject = $this->payload['subject'] ?? 'Bienvenue sur notre service';
                $body = $this->payload['body'] ?? ("Votre compte %s a été créé.\nMerci.");
                $body = sprintf($body, $this->payload['numero_compte'] ?? '');

                Mail::raw($body, function ($m) use ($subject) {
                    $m->to($this->payload['email'])->subject($subject);
                });
            }
        } catch (\Throwable $e) {
            Log::channel('comptes')->error('Failed to send welcome email', ['error' => $e->getMessage(), 'payload' => $this->payload]);
        }

        Log::channel('comptes')->info('SendWelcomeNotificationsJob completed', ['numero_compte' => $this->payload['numero_compte'] ?? null]);
    }
}
