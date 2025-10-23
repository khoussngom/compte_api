<?php

namespace App\Services;

use \App\Models\Compte;
use App\Repositories\CompteRepository;
use Illuminate\Database\Eloquent\Collection;
class CompteService
{
    protected $compteRepository;

    public function __construct()
    {
        $this->compteRepository = new CompteRepository();
    }

    public function getAllComptes():Collection
    {
        return $this->compteRepository->all();
    }
    public function getCompteById($id)
    {
        return $this->compteRepository->findOrFail($id);
    }
    public function createCompte(array $data)
    {
        return $this->compteRepository->create($data);
    }
    public function updateCompte($id, array $data)
    {
        return $this->compteRepository->update($id, $data);
    }

    public function deleteCompte($id)
    {
        $compte = $this->compteRepository->findOrFail($id);
        return $compte->delete();
    }
}
