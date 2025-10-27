<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'numeroCompte' => $this->numero_compte,
            'titulaire' => isset($this->client) ? trim(($this->client->nom ?? '') . ' ' . ($this->client->prenom ?? '')) : ($this->titulaire_compte ?? null),
            'type' => $this->type_compte ?? $this->type,
            'solde' => $this->solde,
            'devise' => $this->devise ?? 'FCFA',
            'dateCreation' => optional($this->date_creation ?? $this->created_at)->toIso8601String(),
            'statut' => $this->statut_compte ?? $this->statut,
            'motifBlocage' => $this->motif_blocage ?? null,
            'dateBlocage' => optional($this->date_debut_blocage)->toIso8601String(),
            'dateDeblocagePrevue' => optional($this->date_fin_blocage)->toIso8601String(),
            'dateDeblocage' => optional($this->date_deblocage)->toIso8601String(),
            'dateFermeture' => optional($this->date_fermeture)->toIso8601String(),
            'metadata' => [
                'derniereModification' => optional($this->updated_at)->toIso8601String(),
                'version' => $this->version ?? 1,
            ],
        ];
    }
}
