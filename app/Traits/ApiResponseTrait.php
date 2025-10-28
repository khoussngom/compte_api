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
        // If the caller passed a LengthAwarePaginator as $pagination, build
        // consistent HATEOAS metadata (pagination + links). Otherwise fall
        // back to the legacy behaviour where $pagination is an array.
        if ($pagination instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $p = $pagination;
            $meta = [
                'currentPage' => $p->currentPage(),
                'totalPages' => $p->lastPage(),
                'totalItems' => $p->total(),
                'itemsPerPage' => $p->perPage(),
                'hasNext' => $p->currentPage() < $p->lastPage(),
                'hasPrevious' => $p->currentPage() > 1,
            ];

            $links = [
                'self' => $p->url($p->currentPage()),
                'first' => $p->url(1),
                'last' => $p->url($p->lastPage()),
                'next' => $p->nextPageUrl(),
                'previous' => $p->previousPageUrl(),
            ];

            // Enrich each item with basic HATEOAS links when possible.
            $enriched = $this->enrichItemsWithLinks($data, 'compte');

            return response()->json([
                'success' => true,
                'data' => $enriched,
                'message' => $message,
                'pagination' => $meta,
                'links' => $links,
            ], $code);
        }

        return response()->json([
            'success' => true,
            'data' => $this->enrichItemsWithLinks($data, 'compte'),
            'message' => $message,
            'pagination' => $pagination,
        ], $code);
    }

    /**
     * Enrich collection items with minimal HATEOAS links when identifiable.
     * Currently targets 'compte' items (models or arrays) and adds a `links`
     * object with self/archive/bloquer/debloquer endpoints where applicable.
     *
     * @param array $items
     * @param string|null $type
     * @return array
     */
    protected function enrichItemsWithLinks($items, $type = null): array
    {
        if (!is_array($items)) {
            return $items;
        }

        $baseUrl = config('app.url') ?: url('/');

        return array_map(function ($item) use ($baseUrl, $type) {
            // If item already contains links, leave it as-is.
            if (is_array($item) && array_key_exists('links', $item)) {
                return $item;
            }

            // Extract identifier (prefer id, fallback to numeroCompte/numero_compte)
            $id = null;
            $numero = null;

            if (is_object($item)) {
                // Eloquent model
                $id = $item->id ?? null;
                $numero = $item->numero_compte ?? $item->numeroCompte ?? null;
                $arr = method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
            } else {
                $arr = $item;
                $id = $arr['id'] ?? null;
                $numero = $arr['numeroCompte'] ?? $arr['numero_compte'] ?? null;
            }

            // Build links only when we have at least one identifier
            if ($id || $numero) {
                $ident = $id ?: $numero;
                $links = [
                    'self' => rtrim($baseUrl, '/') . '/api/v1/comptes/' . $ident,
                    'archive' => rtrim($baseUrl, '/') . '/api/v1/comptes/' . $ident . '/archive',
                    'bloquer' => rtrim($baseUrl, '/') . '/api/v1/comptes/' . $ident . '/bloquer',
                    'debloquer' => rtrim($baseUrl, '/') . '/api/v1/comptes/' . $ident . '/debloquer',
                ];

                // Attach links into array representation
                $arr['links'] = $links;
                return $arr;
            }

            return $item;
        }, $items);
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
