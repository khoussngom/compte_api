<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use App\Services\MessageServiceInterface;

class TwilioMessageService implements MessageServiceInterface
{
    public function __construct(private \Twilio\Rest\Client $client, private string $from)
    {}

    /**
     * {@inheritdoc}
     */
    public function sendMessage(string $to, string $message): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message,
            ]);

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Twilio send error: ' . $e->getMessage());
            return false;
        }
    }
}
