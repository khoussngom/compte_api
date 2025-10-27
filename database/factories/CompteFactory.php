<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompteFactory extends Factory
{
    protected $model = Compte::class;

    public function definition()
    {
        return [
            'id' => (string) Str::uuid(),
            'client_id' => Client::factory()->withUser(),
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
