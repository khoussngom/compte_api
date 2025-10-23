<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Compte;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;
class CompteFactory extends Factory
{
    protected $model = Compte::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'numero_compte' => 'CPT'.$this->faker->unique()->numerify('########'),
            'titulaire_compte' => $this->faker->name(),
            'type_compte' => $this->faker->randomElement(['epargne', 'courant']),
            'devise' => $this->faker->randomElement(['CFA', 'USD', 'EUR']),
            'date_creation' => $this->faker->date(),
            'statut_compte' => $this->faker->randomElement(['actif', 'bloque']),
            'motif_blocage' => $this->faker->optional()->sentence(),
            'version' => 1,
            'user_id' => User::factory(),
            'client_id' => \App\Models\Client::factory(),
        ];
    }
}
