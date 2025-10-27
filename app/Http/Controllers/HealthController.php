<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use App\Traits\ApiResponseTrait;

class HealthController extends Controller
{
    use ApiResponseTrait;
    public function index(Request $request): JsonResponse
    {
        $result = [
            'ok' => false,
            'app_env' => env('APP_ENV'),
            'checks' => [],
        ];

        $envChecks = [];
        $dbHost = env('DB_HOST');
        $dbPort = env('DB_PORT');
        $dbName = env('DB_DATABASE');
        $dbUrl = env('DATABASE_URL') ?? env('DB_URL');

        $envChecks['db_host_present'] = !empty($dbHost);
        $envChecks['db_port_numeric'] = is_numeric($dbPort);
        $envChecks['db_database_present'] = !empty($dbName);
        $envChecks['database_url_present'] = !empty($dbUrl);

    $suspect = false;
    $suspectPatterns = ['client_encoding=', 'dbname=', '${DB_PORT}'];
        foreach ($suspectPatterns as $p) {
            if ($dbHost && str_contains($dbHost, $p)) {
                $suspect = true;
            }
            if ($dbPort && str_contains($dbPort, $p)) {
                $suspect = true;
            }
            if ($dbUrl && str_contains($dbUrl, $p)) {
                $suspect = true;
            }
        }
        $envChecks['env_values_suspect'] = $suspect;

        $result['checks']['env_sanity'] = $envChecks;

        try {
            $dbConfig = config('database.connections.pgsql', []);
            $safeDbConfig = [
                'host' => $dbConfig['host'] ?? null,
                'port' => $dbConfig['port'] ?? null,
                'database' => $dbConfig['database'] ?? null,
                'username' => $dbConfig['username'] ?? null,
                'sslmode' => $dbConfig['sslmode'] ?? null,
            ];
            $result['checks']['config_database'] = $safeDbConfig;
        } catch (\Throwable $e) {
            $result['checks']['config_database'] = 'error';
            $result['checks']['config_database_error'] = $e->getMessage();
        }

        try {
            DB::connection()->getPdo();
            $result['checks']['db_connection'] = true;
        } catch (\Throwable $e) {
            $result['checks']['db_connection'] = false;
            $result['checks']['db_error'] = $e->getMessage();
            Log::error('Health check DB connection failed: '.$e->getMessage());
        }

        $tables = ['users', 'comptes', 'account_transactions'];
        foreach ($tables as $t) {
            try {
                $exists = Schema::hasTable($t);
                $result['checks']["table_{$t}"] = $exists;
                if ($exists) {

                    try {
                        $count = DB::table($t)->limit(1)->count();
                        $result['checks']["table_{$t}_count_sample"] = $count;
                    } catch (\Throwable $e) {
                        $result['checks']["table_{$t}_count_error"] = $e->getMessage();
                        Log::error("Health check table {$t} count failed: {$e->getMessage()}");
                    }
                }
            } catch (\Throwable $e) {
                $result['checks']["table_{$t}"] = 'error';
                $result['checks']["table_{$t}_error"] = $e->getMessage();
                Log::error("Health check table {$t} error: {$e->getMessage()}");
            }
        }

        $result['ok'] = ($result['checks']['db_connection'] ?? false) === true;

        if ($result['ok']) {
            return $this->respondWithData($result, 'Health check OK', 200);
        }

        return $this->errorResponse('Health check failed', 500, $result);
    }
}
