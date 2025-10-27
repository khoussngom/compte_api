<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Route as RouteFacade;

class DiagController extends BaseController
{
    use ApiResponseTrait;
    public function index(Request $request)
    {
        $secret = env('DIAG_SECRET');

        if (!env('APP_DEBUG', false)) {
            if ($secret) {
                $provided = $request->header('X-DIAG-SECRET') ?? $request->query('secret');
                if (!$provided || !hash_equals((string) $secret, (string) $provided)) {
                    return $this->errorResponse('Forbidden', 403);
                }
            } else {
                return $this->errorResponse('Forbidden: set DIAG_SECRET or enable APP_DEBUG', 403);
            }
        }

        $info = [
            'app_env' => env('APP_ENV'),
            'app_debug' => (bool) env('APP_DEBUG', false),
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
        ];

        $routes = [];
        foreach (RouteFacade::getRoutes() as $r) {
            try {
                $uri = $r->uri();
            } catch (\Throwable $e) {
                continue;
            }
            if (strpos($uri, 'comptes') !== false || strpos($uri, 'api/v1') !== false) {
                $routes[] = [
                    'uri' => $uri,
                    'methods' => $r->methods(),
                    'name' => $r->getName(),
                ];
            }
        }
        $info['routes_sample'] = $routes;

        try {
            $res = DB::select('SELECT 1 AS ok');
            $info['db_ok'] = isset($res[0]) && ($res[0]->ok == 1 || $res[0]->ok === '1');
        } catch (\Throwable $e) {
            $info['db_ok'] = false;
            $info['db_error'] = $e->getMessage();
        }

        try {
            $info['migrations_table'] = Schema::hasTable('migrations');
            if ($info['migrations_table']) {
                $info['migrations_count'] = DB::table('migrations')->count();
            }
        } catch (\Throwable $e) {
            $info['migrations_table'] = false;
            $info['migrations_error'] = $e->getMessage();
        }

        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            try {
                $lines = file($logPath, 6);
                $last = array_slice($lines, -200);
                $info['last_logs'] = implode("\n", $last);
            } catch (\Throwable $e) {
                $info['last_logs_error'] = $e->getMessage();
            }
        } else {
            $info['last_logs'] = null;
        }

        return $this->respondWithData($info);
    }
}
