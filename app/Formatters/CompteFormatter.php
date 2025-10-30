<?php

namespace App\Formatters;

use App\Models\Compte;

class CompteFormatter
{
    public static function format(?Compte $c): ?array
    {
        if (! $c) {
            return null;
        }

        return [
            'id' => $c->id,
            'numeroCompte' => $c->numero_compte,
            'type' => $c->type_compte,
            'solde' => $c->solde,
            'devise' => $c->devise,
            'statut' => $c->statut_compte,
            'dateCreation' => optional($c->date_creation)->toIso8601String(),
        ];
    }
}
