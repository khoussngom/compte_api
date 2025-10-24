<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

trait ApiQueryTrait
{
    public function applyQueryFilters(Builder $query, Request $request): Builder
    {
        // Filtrage
        if ($type = $request->query('type')) {
            $query->where('type_compte', $type);
        }
        if ($statut = $request->query('statut')) {
            $query->where('statut_compte', $statut);
        }
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('titulaire_compte', 'like', "%$search%")
                  ->orWhere('numero_compte', 'like', "%$search%");
            });
        }

        // Tri
        $sort = $request->query('sort', 'date_creation');
        $order = $request->query('order', 'desc');
        $allowedSorts = ['date_creation', 'solde', 'titulaire_compte'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $order);
        }

        // Pagination
        $limit = min((int) $request->query('limit', 10), 100);
        $page = max((int) $request->query('page', 1), 1);
        return $query->paginate($limit, ['*'], 'page', $page);
    }
}
