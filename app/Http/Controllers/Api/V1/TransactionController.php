<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\TransactionService;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;

class TransactionController extends Controller
{
    protected $service;

    public function __construct(TransactionService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *   path="/api/v1/transactions",
     *   summary="Liste des transactions (admin) ou transactions du client connecté",
     *   tags={"Transactions"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $this->service->listForUser($user);
        $pag = $query->paginate(25);
        return response()->json($pag);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/transactions/{id}",
     *   tags={"Transactions"},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show($id)
    {
        $transaction = $this->service->find($id);
        if (! $transaction) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->authorize('view', $transaction);
        return response()->json($transaction);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/transactions",
     *   tags={"Transactions"},
     *   @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/StoreTransactionRequest")),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(StoreTransactionRequest $request)
    {
        $user = Auth::user();
        $this->authorize('manage', Transaction::class);

        try {
            $transaction = $this->service->create($request->validated(), $user);
            return response()->json(['message' => 'Transaction effectuée avec succès','data' => $transaction], 201);
        } catch (\Exception $e) {
            // Domain errors return 422 so tests can assert validation-like failures.
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * @OA\Put(
     *   path="/api/v1/transactions/{id}",
     *   tags={"Transactions"},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/UpdateTransactionRequest")),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateTransactionRequest $request, $id)
    {
        $this->authorize('manage', Transaction::class);
        $transaction = Transaction::findOrFail($id);
        $res = $this->service->update($transaction, $request->validated());
        return response()->json(['message' => 'Transaction mise à jour','data' => $res]);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/transactions/{id}",
     *   tags={"Transactions"},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        $this->authorize('manage', Transaction::class);
        $transaction = Transaction::findOrFail($id);
        $this->service->destroy($transaction);
        return response()->json(['message' => 'Transaction supprimée']);
    }
}
