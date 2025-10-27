<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('post')) {
            $this->logCreationOperation($request, $response);
        }

        return $response;
    }

    private function logCreationOperation(Request $request, Response $response): void
    {
        $operationName = $this->getOperationName($request);
        $resource = $this->getResourceName($request);

        if ($operationName && $resource) {
            $logData = [
                'date_heure' => now()->toISOString(),
                'host' => $request->getHost(),
                'nom_operation' => $operationName,
                'ressource' => $resource,
                'status_code' => $response->getStatusCode(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ];

            Log::info('Opération de création exécutée', $logData);
        }
    }


    private function getOperationName(Request $request): ?string
    {
        $path = $request->path();

        if (str_contains($path, 'accounts')) {
            return 'Création de compte bancaire';
        }

        if (str_contains($path, 'users')) {
            return 'Création d\'utilisateur';
        }

        if (str_contains($path, 'clients')) {
            return 'Création de client';
        }

        if (str_contains($path, 'comptes')) {
            return 'Création de compte';
        }

        if (str_contains($path, 'transactions')) {
            return 'Création de transaction';
        }

        return 'Création de ressource';
    }

    private function getResourceName(Request $request): ?string
    {
        $path = $request->path();

        if (preg_match('/\/api\/([^\/]+)/', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
