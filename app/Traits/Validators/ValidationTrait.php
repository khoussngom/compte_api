<?php
namespace App\Traits\Validators;

use App\Models\User;
use App\Models\Client;

trait ValidationTrait
{
    protected function isValidSenegalPhone(?string $phone): bool
    {
        if (!$phone) return false;
        if (substr($phone, 0, 4) !== '+221') return false;
        if (strlen($phone) !== 13) return false;

        $op = substr($phone, 4, 2);
        $validOps = ['77','78','70','76','75'];
        if (! in_array($op, $validOps, true)) return false;

        $rest = substr($phone, 6);
        return ctype_digit($rest) && strlen($rest) === 7;
    }

    protected function isValidNci(?string $nci): bool
    {
        if (!$nci) return false;
        if (strlen($nci) !== 13) return false;
        if ($nci[0] !== '1' && $nci[0] !== '2') return false;
        return ctype_digit($nci);
    }

    public function validateBlocagePayload(array $data): array
    {
        $errors = [];

        if (empty($data['motif']) || !is_string($data['motif'])) {
            $errors['motif'] = 'Le motif est requis et doit être une chaîne.';
        } elseif (mb_strlen($data['motif']) > 1024) {
            $errors['motif'] = 'Le motif est trop long (max 1024 caractères).';
        }

        if (!isset($data['duree']) || !is_numeric($data['duree']) || (int)$data['duree'] < 1) {
            $errors['duree'] = 'La durée est requise et doit être un entier >= 1.';
        }

        $validUnites = ['jours','mois','annees'];
        if (empty($data['unite']) || !is_string($data['unite']) || !in_array($data['unite'], $validUnites, true)) {
            $errors['unite'] = 'L\'unité est requise et doit être une des valeurs: jours, mois, annees.';
        }

        return $errors;
    }

    public function validateDeblocagePayload(array $data): array
    {
        $errors = [];
        if (empty($data['motif']) || !is_string($data['motif'])) {
            $errors['motif'] = 'Le motif est requis et doit être une chaîne.';
        } elseif (mb_strlen($data['motif']) > 1024) {
            $errors['motif'] = 'Le motif est trop long (max 1024 caractères).';
        }
        return $errors;
    }

    /**
     * Validate payload for compte update.
     * $clientId may come as int or string (depending on lookup). Accept any and
     * use it only when numeric to avoid TypeError and invalid UUID casts.
     *
     * @param array $data
     * @param mixed $clientId
     * @return array
     */
    public function validateUpdateComptePayload(array $data, $clientId = null): array
    {
        $errors = [];

        $hasTitulaire = array_key_exists('titulaire', $data) && $data['titulaire'] !== null && $data['titulaire'] !== '';
        $hasClient = array_key_exists('informationsClient', $data) && is_array($data['informationsClient']) && count(array_filter($data['informationsClient'], function ($v) {
            return $v !== null && $v !== '';
        })) > 0;

        if (! $hasTitulaire && ! $hasClient) {
            $errors['update'] = 'Au moins un champ doit être fourni pour la mise à jour.';
            return $errors;
        }

        if ($hasTitulaire) {
            if (!is_string($data['titulaire']) || mb_strlen($data['titulaire']) > 255) {
                $errors['titulaire'] = 'Le titulaire doit être une chaîne de max 255 caractères.';
            }
        }

        if ($hasClient) {
            $c = $data['informationsClient'];
            if (array_key_exists('telephone', $c) && $c['telephone'] !== null && $c['telephone'] !== '') {
                if (! $this->isValidSenegalPhone($c['telephone'])) {
                    $errors['informationsClient.telephone'] = 'Numéro de téléphone sénégalais invalide, format attendu +22177xxxxxxx.';
                } else {
                    // uniqueness check against users table
                    $query = User::where('telephone', $c['telephone']);
                    // Only apply client id exclusion when we have a numeric id.
                    if (!is_null($clientId) && is_numeric($clientId)) {
                        $query->where('id', '!=', (int) $clientId);
                    }
                    if ($query->exists()) {
                        $errors['informationsClient.telephone'] = 'Le numéro de téléphone est déjà utilisé.';
                    }
                }
            }

            if (array_key_exists('email', $c) && $c['email'] !== null && $c['email'] !== '') {
                if (! filter_var($c['email'], \FILTER_VALIDATE_EMAIL)) {
                    $errors['informationsClient.email'] = 'Adresse email invalide.';
                } else {
                    $query = User::where('email', $c['email']);
                    if (!is_null($clientId) && is_numeric($clientId)) {
                        $query->where('id', '!=', (int) $clientId);
                    }
                    if ($query->exists()) {
                        $errors['informationsClient.email'] = 'L\'email est déjà utilisé.';
                    }
                }
            }

            if (array_key_exists('password', $c) && $c['password'] !== null && $c['password'] !== '') {
                if (!is_string($c['password']) || mb_strlen($c['password']) < 8) {
                    $errors['informationsClient.password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
                }
            }

            if (array_key_exists('nci', $c) && $c['nci'] !== null && $c['nci'] !== '') {
                if (! $this->isValidNci($c['nci'])) {
                    $errors['informationsClient.nci'] = 'NCI invalide. Doit contenir 13 chiffres et commencer par 1 ou 2.';
                }
            }
        }

        return $errors;
    }

    public function validateAccountStorePayload(array $data): array
    {
        $errors = [];

        $types = ['cheque','epargne'];
        if (empty($data['type']) || !is_string($data['type']) || !in_array($data['type'], $types, true)) {
            $errors['type'] = 'Le type de compte est requis et doit être cheque ou epargne.';
        }

        if (!isset($data['soldeInitial']) || !is_numeric($data['soldeInitial']) || $data['soldeInitial'] < 10000) {
            $errors['soldeInitial'] = 'Le solde initial est requis et doit être >= 10000.';
        }

        $devises = ['FCFA','XOF'];
        if (empty($data['devise']) || !in_array($data['devise'], $devises, true)) {
            $errors['devise'] = 'La devise est requise et doit être FCFA ou XOF.';
        }

        if (!isset($data['solde']) || !is_numeric($data['solde']) || $data['solde'] < 0) {
            $errors['solde'] = 'Le solde est requis et doit être >= 0.';
        }

        if (empty($data['client']) || !is_array($data['client'])) {
            $errors['client'] = 'Les informations du client sont requises.';
            return $errors;
        }

        $c = $data['client'];
        if (array_key_exists('id', $c) && !is_null($c['id'])) {
            // Accept UUID or numeric ids; just ensure the client exists.
            if (!Client::where('id', $c['id'])->exists()) {
                $errors['client.id'] = 'Client.id invalide.';
            }
        }

        if (empty($c['titulaire']) || !is_string($c['titulaire'])) {
            $errors['client.titulaire'] = 'Le nom du titulaire est requis.';
        }

        if (empty($c['nci']) || ! $this->isValidNci($c['nci'])) {
            $errors['client.nci'] = 'Le NCI est requis et doit être valide.';
        }

        if (empty($c['email']) || ! filter_var($c['email'], \FILTER_VALIDATE_EMAIL)) {
            $errors['client.email'] = 'L\'email est requis et doit être valide.';
        }

        if (empty($c['telephone']) || ! $this->isValidSenegalPhone($c['telephone'])) {
            $errors['client.telephone'] = 'Le numéro de téléphone est requis et doit être un numéro sénégalais valide.';
        } else {
            // allow existing telephone for account creation; the controller will
            // attach a new compte to an existing client when appropriate.
        }

        if (empty($c['adresse']) || !is_string($c['adresse'])) {
            $errors['client.adresse'] = 'L\'adresse est requise.';
        }

        return $errors;
    }

    public function validateBlocageComptePayload(array $data): array
    {
        $errors = [];
        if (empty($data['date_debut_blocage']) || strtotime($data['date_debut_blocage']) === false) {
            $errors['date_debut_blocage'] = 'La date de début du blocage est requise et doit être une date valide.';
        }
        if (empty($data['date_fin_blocage']) || strtotime($data['date_fin_blocage']) === false) {
            $errors['date_fin_blocage'] = 'La date de fin du blocage est requise et doit être une date valide.';
        }
        if (empty($errors['date_debut_blocage']) && empty($errors['date_fin_blocage'])) {
            if (strtotime($data['date_fin_blocage']) <= strtotime($data['date_debut_blocage'])) {
                $errors['date_fin_blocage'] = 'La date de fin doit être postérieure à la date de début.';
            }
        }
        if (empty($data['motif_blocage']) || !is_string($data['motif_blocage'])) {
            $errors['motif_blocage'] = 'Le motif de blocage est requis.';
        }
        return $errors;
    }

    public function validateFilterPayload(array $data): array
    {
        $errors = [];
    // Accept both 'cheque' and 'courant' as possible spellings used across the
    // codebase and persisted data. Keep 'epargne' (accent/without accent)
    // and 'professionnel' as valid types as well.
    $types = ['cheque', 'courant', 'épargne', 'epargne', 'professionnel'];
        if (array_key_exists('type', $data) && $data['type'] !== null && !in_array($data['type'], $types, true)) {
            $errors['type'] = 'Le type de compte doit être épargne, courant ou professionnel.';
        }
        $statuts = ['actif','inactif','bloque','bloqué'];
        if (array_key_exists('statut', $data) && $data['statut'] !== null && !in_array($data['statut'], $statuts, true)) {
            $errors['statut'] = 'Le statut doit être actif, inactif ou bloqué.';
        }
        if (array_key_exists('search', $data) && $data['search'] !== null && (!is_string($data['search']) || mb_strlen($data['search']) > 100)) {
            $errors['search'] = 'Le champ de recherche doit être une chaîne de max 100 caractères.';
        }
        $sorts = ['date_creation','solde','titulaire_compte'];
        if (array_key_exists('sort', $data) && $data['sort'] !== null && !in_array($data['sort'], $sorts, true)) {
            $errors['sort'] = 'Le champ de tri n\'est pas valide.';
        }
        if (array_key_exists('order', $data) && $data['order'] !== null && !in_array($data['order'], ['asc','desc'], true)) {
            $errors['order'] = 'L\'ordre de tri doit être asc ou desc.';
        }
        if (array_key_exists('limit', $data) && $data['limit'] !== null) {
            if (!is_numeric($data['limit']) || (int)$data['limit'] < 1 || (int)$data['limit'] > 100) {
                $errors['limit'] = 'La pagination doit être un entier entre 1 et 100.';
            }
        }
        if (array_key_exists('page', $data) && $data['page'] !== null) {
            if (!is_numeric($data['page']) || (int)$data['page'] < 1) {
                $errors['page'] = 'La page doit être un entier >= 1.';
            }
        }
        return $errors;
    }

    public function validateMessagePayload(array $data): array
    {
        $errors = [];
        if (empty($data['to']) || !is_string($data['to'])) {
            $errors['to'] = 'Le destinataire (to) est requis et doit être une chaîne.';
        }
        if (empty($data['message']) || !is_string($data['message'])) {
            $errors['message'] = 'Le message est requis et doit être une chaîne.';
        }
        return $errors;
    }
}
