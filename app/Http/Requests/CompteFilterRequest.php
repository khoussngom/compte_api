<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompteFilterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => 'nullable|string|in:épargne,courant,professionnel',
            'statut' => 'nullable|string|in:actif,inactif,bloqué',
            'search' => 'nullable|string|max:100',
            'sort' => 'nullable|string|in:date_creation,solde,titulaire_compte',
            'order' => 'nullable|string|in:asc,desc',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'type.in' => 'Le type de compte doit être épargne, courant ou professionnel.',
            'statut.in' => 'Le statut doit être actif, inactif ou bloqué.',
            'sort.in' => 'Le champ de tri n’est pas valide.',
            'order.in' => 'L’ordre de tri doit être asc ou desc.',
            'limit.max' => 'La pagination ne peut dépasser 100 éléments.',
        ];
    }
}
