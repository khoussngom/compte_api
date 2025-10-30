<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\MessageServiceInterface;

class EmailMessageService implements MessageServiceInterface
{

    public function sendMessage(string $to, string $message): bool
    {
        try {
            Mail::raw($message, function ($m) use ($to) {
                $m->to($to)->subject('Notification');
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('Email send error: ' . $e->getMessage());
            return false;
        }
    }
}
