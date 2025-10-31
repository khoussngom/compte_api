<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TransactionService;

class DashboardController extends Controller
{
    protected $service;

    public function __construct(TransactionService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(path="/api/v1/dashboard", tags={"Dashboard"}, security={{"bearerAuth":{}}}, @OA\Response(response=200, description="OK"))
     */
    public function global(Request $request)
    {
        $request->user();
        // only admin
        if (! $request->user() || ! $request->user()->admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($this->service->globalDashboard());
    }

    /**
     * @OA\Get(path="/api/v1/dashboard/me", tags={"Dashboard"}, security={{"bearerAuth":{}}}, @OA\Response(response=200, description="OK"))
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($this->service->personalDashboard($user));
    }
}
