<?php

namespace App\Providers;

use App\Services\EmailMessageService;
use App\Services\TwilioMessageService;
use Illuminate\Support\ServiceProvider;
use App\Services\MessageServiceInterface;
use Twilio\Rest\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the MessageServiceInterface to a concrete implementation.
        // Default: TwilioMessageService (you can change to EmailMessageService or a custom implementation).
        $this->app->bind(MessageServiceInterface::class, function ($app) {
            $sid = config('services.twilio.sid') ?: env('TWILIO_SID');
            $token = config('services.twilio.token') ?: env('TWILIO_TOKEN');
            $from = config('services.twilio.from') ?: env('TWILIO_FROM', '');

            if (empty($sid) || empty($token)) {
                // Fallback to EmailMessageService if Twilio not configured
                return new EmailMessageService();
            }

            try {
                $client = new Client($sid, $token);
                return new TwilioMessageService($client, $from);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Twilio client construction failed: ' . $e->getMessage());
                // Fallback to EmailMessageService
                return new EmailMessageService();
            }
        });

        // Also register EmailMessageService if you want to resolve it directly by class.
        $this->app->singleton(EmailMessageService::class, function ($app) {
            return new EmailMessageService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
