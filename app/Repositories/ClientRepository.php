<?php

namespace App\Repositories;

use App\Models\Client;

class ClientRepository
{
    public function create(array $data): Client
    {
        return Client::create($data);
    }

    public function findByNci(string $nci): ?Client
    {
        return Client::where('nci', $nci)->first();
    }
}
