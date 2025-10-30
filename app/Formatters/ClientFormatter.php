<?php

namespace App\Formatters;

use App\Models\Client;

class ClientFormatter
{
    public static function format(?Client $c): ?array
    {
        if (! $c) {
            return null;
        }

        return [
            'id' => $c->id,
            'nom' => $c->nom,
            'prenom' => $c->prenom,
            'email' => $c->email,
            'telephone' => $c->telephone,
            'nci' => $c->nci,
            'adresse' => $c->adresse,
        ];
    }
}
