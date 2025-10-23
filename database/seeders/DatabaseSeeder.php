<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory(10)->create()->each(function ($user) {
            Compte::factory(3)->create([
                'user_id' => $user->id
            ]);
        });

        $comptes = Compte::all();
        Transaction::factory(30)->make()->each(function ($transaction) use ($comptes) {
            $transaction->compte_id = $comptes->random()->id;
            $transaction->save();
        });
    }
}
