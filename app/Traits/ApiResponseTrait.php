<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use App\Models\Compte;

trait ApiResponseTrait
{
    public function successResponse($data, $message = null, $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * Standard error response. Optional $data allows passing structured error details.
     */
    public function errorResponse($message = null, $code = 400, $data = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $payload['data'] = $data;
        } else {
            $payload['data'] = null;
        }

        return response()->json($payload, $code);
    }

    /**
     * Return a structured validation error response which includes the errors array.
     */
    public function validationErrorResponse(array $errors, $message = 'Validation failed', $code = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Generic wrapper for success payloads — move controllers away from assembling responses.
     */
    public function respondWithData($data, $message = null, $code = 200): JsonResponse
    {
        return $this->successResponse($data, $message, $code);
    }

    /**
     * Wrapper for collections.
     */
    public function respondWithCollection($collection, $message = null, $code = 200): JsonResponse
    {
        return $this->successResponse($collection, $message, $code);
    }

    /**
     * Wrapper for resources (JsonResource instances or arrays).
     */
    public function respondWithResource($resource, $message = null, $code = 200): JsonResponse
    {
        return $this->successResponse($resource, $message, $code);
    }

    /**
     * Specialized formatter for a Compte model to keep controllers thin.
     */
    public function respondWithCompteModel(Compte $compte, $titulaire = null, $message = null, $code = 200): JsonResponse
    {
        $payload = [
            'id' => (string) $compte->id,
            'numeroCompte' => $compte->numero_compte,
            'titulaire' => $titulaire ?? $compte->titulaire,
            'type' => $compte->type_compte,
            'solde' => $compte->solde,
            'devise' => $compte->devise,
            'dateCreation' => optional($compte->created_at)->toIso8601String(),
            'statut' => $compte->statut_compte,
            'metadata' => [
                'derniereModification' => optional($compte->updated_at)->toIso8601String(),
                'version' => $compte->version ?? 1,
            ],
        ];

        return $this->successResponse($payload, $message, $code);
    }

    public function paginatedResponse($data, $pagination, $message = null, $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'pagination' => $pagination,
        ], $code);
    }

    public function notFoundResponse($message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
        ], 404);
    }
}
