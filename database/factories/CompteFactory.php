<?php

namespace Database\Factories;

use App\Models\Compte;
use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompteFactory extends Factory
{
    protected $model = Compte::class;

    public function definition()
    {
        return [
            'id' => (string) Str::uuid(),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'numero_compte' => 'ACC-'.now()->format('Ymd').'-'.mt_rand(1000,9999),
            'titulaire_compte' => $this->faker->name(),
            'type_compte' => $this->faker->randomElement(['courant','epargne','cheque']),
            'devise' => 'FCFA',
            'date_creation' => now()->toDateString(),
            'statut_compte' => 'actif',
            'solde' => $this->faker->randomFloat(2, 0, 100000),
            'version' => 1,
            'archived' => false,
        ];
    }
}
