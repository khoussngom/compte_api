<?php

namespace App\Providers;

use App\Services\EmailMessageService;
use App\Services\TwilioMessageService;
use Illuminate\Support\ServiceProvider;
use App\Services\MessageServiceInterface;
use Twilio\Rest\Client;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as FrameworkVerifyCsrf;
use Illuminate\Support\Facades\File;

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
            // Use explicit account SID when possible (AC...), and optionally a Messaging Service SID (MG...)
            $accountSid = config('services.twilio.account_sid') ?: env('TWILIO_ACCOUNT_SID');
            $messagingServiceSid = config('services.twilio.messaging_service_sid') ?: env('TWILIO_MESSAGING_SID');
            // Backwards compat: TWILIO_SID may contain either an AC... or MG... value
            $legacySid = config('services.twilio.sid') ?: env('TWILIO_SID');
            $token = config('services.twilio.token') ?: env('TWILIO_TOKEN');
            $from = config('services.twilio.from') ?: env('TWILIO_FROM', '');

            // If accountSid not provided but legacy contains AC..., use it as accountSid
            if (empty($accountSid) && !empty($legacySid) && str_starts_with($legacySid, 'AC')) {
                $accountSid = $legacySid;
            }

            // If messagingServiceSid not provided but legacy contains MG..., use it
            if (empty($messagingServiceSid) && !empty($legacySid) && str_starts_with($legacySid, 'MG')) {
                $messagingServiceSid = $legacySid;
            }

            if (empty($accountSid) || empty($token)) {
                // Fallback to EmailMessageService if Twilio not configured properly
                return new EmailMessageService();
            }

            try {
                $client = new Client($accountSid, $token);
                return new TwilioMessageService($client, $from, $messagingServiceSid);
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
        // Ensure our application-level VerifyCsrfToken middleware (App\Http\Middleware\VerifyCsrfToken)
        // is used in place of the framework class when resolving middleware from the container.
        // This allows our diagnostic logging and local exemptions to run even when the
        // framework references the middleware by the framework FQCN.
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, \App\Http\Middleware\VerifyCsrfToken::class);

        // In local environment, also add explicit exclusions to the framework middleware
        // so that requests matching our local API mount do not trigger CSRF checks.
        if (app()->environment('local')) {
            FrameworkVerifyCsrf::except([
                'khouss.ngom/api/v1/*',
                'khouss.ngom/api/v1',
                'api/v1/*',
            ]);
        }

        // Ensure Passport RSA keys exist — if not, generate them at runtime.
        // This prevents "Invalid key supplied" errors when the oauth keys are missing
        // (for example after a fresh checkout or in some deployment environments).
        // We generate a 2048-bit RSA keypair and store them under storage/ (same location
        // as `php artisan passport:keys` would). This is safe for development and
        // acceptable for many deployments, but for production you may prefer to
        // provision keys externally and keep them out of the repo.
        try {
            $privatePath = storage_path('oauth-private.key');
            $publicPath = storage_path('oauth-public.key');

            if (!File::exists($privatePath) || !File::exists($publicPath)) {
                // Generate a new 2048-bit RSA key pair
                $config = [
                    "private_key_bits" => 2048,
                    "private_key_type" => \OPENSSL_KEYTYPE_RSA,
                ];

                $res = \openssl_pkey_new($config);
                if ($res === false) {
                    \Log::error('Failed to generate RSA keypair for Passport: ' . \openssl_error_string());
                } else {
                    // Export private key
                    \openssl_pkey_export($res, $privateKeyPem);

                    // Extract public key
                    $details = \openssl_pkey_get_details($res);
                    $publicKeyPem = $details['key'] ?? null;

                    if ($privateKeyPem && $publicKeyPem) {
                        File::put($privatePath, $privateKeyPem);
                        File::put($publicPath, $publicKeyPem);
                        @chmod($privatePath, 0600);
                        @chmod($publicPath, 0600);
                        \Log::info('Generated Passport RSA keypair at storage/oauth-*.key');
                    } else {
                        \Log::error('Failed to obtain PEM contents when generating Passport keys');
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Error while ensuring Passport keys exist: ' . $e->getMessage());
        }
    }
}
