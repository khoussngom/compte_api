<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Client;

class CreateAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_account_for_new_user_and_client()
    {
        $payload = [
            'type' => 'cheque',
            'soldeInitial' => 500000,
            'devise' => 'FCFA',
            'solde' => 10000,
            'client' => [
                'id' => null,
                'titulaire' => 'Test User',
                'nci' => '1234567890123',
                'email' => 'test@example.com',
                'telephone' => '+221771234567',
                'adresse' => 'Dakar'
            ]
        ];

        $resp = $this->postJson('/api/v1/accounts', $payload);
        $resp->assertStatus(201);
        $resp->assertJsonStructure(['success','message','data' => ['compte','client']]);
    }

    public function test_create_account_for_existing_client_by_telephone()
    {
        $user = User::factory()->create(['email' => 'exist@example.com', 'telephone' => '+221771234567']);
        $client = Client::factory()->create(['user_id' => $user->id, 'telephone' => $user->telephone, 'email' => $user->email]);

        $payload = [
            'type' => 'cheque',
            'soldeInitial' => 20000,
            'devise' => 'FCFA',
            'solde' => 10000,
            'client' => [
                'id' => null,
                'titulaire' => 'Exist User',
                'nci' => '1234567890123',
                'email' => 'exist@example.com',
                'telephone' => '+221771234567',
                'adresse' => 'Dakar'
            ]
        ];

        $resp = $this->postJson('/api/v1/accounts', $payload);
        $resp->assertStatus(201);
        $this->assertDatabaseHas('comptes', ['client_id' => $client->id]);
    }
}
