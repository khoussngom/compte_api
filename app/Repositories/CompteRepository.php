<?php

namespace App\Repositories;

use App\Models\Compte;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CompteRepository
{
    protected $model;

    public function __construct()
    {
        $this->model = new Compte();
    }

    public function all()
    {
        return $this->model->all();
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function findOrFail($id)
    {
        try {
            return $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Compte introuvable');
        }

    }

    public function update($id, array $data)
    {
        $compte = $this->findOrFail($id);
        $compte->update($data);
        return $compte;
    }

}
