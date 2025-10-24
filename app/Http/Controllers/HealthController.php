<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $result = [
            'ok' => false,
            'app_env' => env('APP_ENV'),
            'checks' => [],
        ];

        // DB connection
        try {
            DB::connection()->getPdo();
            $result['checks']['db_connection'] = true;
        } catch (\Throwable $e) {
            $result['checks']['db_connection'] = false;
            $result['checks']['db_error'] = $e->getMessage();
            Log::error('Health check DB connection failed: '.$e->getMessage());
        }

        // check important tables
        $tables = ['users', 'comptes', 'account_transactions'];
        foreach ($tables as $t) {
            try {
                $exists = Schema::hasTable($t);
                $result['checks']["table_{$t}"] = $exists;
                if ($exists) {
                    // try a small count to ensure permissions
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

        return response()->json($result, $result['ok'] ? 200 : 500);
    }
}
