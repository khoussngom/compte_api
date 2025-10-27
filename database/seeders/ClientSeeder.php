<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 clients, each with its own user and 1-3 comptes
        Client::factory()
            ->count(10)
            ->withUser()
            ->create()
            ->each(function (\App\Models\Client $client) {
                // Ensure user is loaded
                $client->load('user');
                $count = rand(1, 3);
                for ($i = 0; $i < $count; $i++) {
                    \App\Models\Compte::create([
                        'client_id' => $client->id,
                        'numero_compte' => \App\Models\Compte::generateNumero(),
                        'user_id' => $client->user->id,
                        'type_compte' => 'courant',
                        'solde' => 0,
                        'devise' => 'FCFA',
                        'statut_compte' => 'actif',
                        'date_creation' => now(),
                    ]);
                }
            });
    }
}
