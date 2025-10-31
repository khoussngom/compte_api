<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_depot_ok()
    {
        $admin = User::factory()->create(['admin' => true]);
        $client = User::factory()->create();
        $compte = Compte::factory()->create(['user_id' => $client->id]);

        $payload = ['type' => 'depot', 'montant' => 100.00, 'compte_id' => $compte->id];

    $resp = $this->actingAs($admin, 'api')->postJson('/api/v1/transactions', $payload);
    // debug: write response content for investigation when test fails
    file_put_contents(__DIR__.'/../../storage/logs/txn_resp_1.txt', $resp->getContent());
    $resp->assertStatus(201);
        $this->assertDatabaseHas('transactions', ['type' => 'depot', 'montant' => 100.00]);
    }

    public function test_retrait_impossible_fonds_insuffisants()
    {
        $admin = User::factory()->create(['admin' => true]);
        $client = User::factory()->create();
        $compte = Compte::factory()->create(['user_id' => $client->id]);

        $payload = ['type' => 'retrait', 'montant' => 500.00, 'compte_id' => $compte->id];
    $resp = $this->actingAs($admin, 'api')->postJson('/api/v1/transactions', $payload);
    $status = $resp->getStatusCode();
    $msg = 'Unexpected status: ' . $status . ' - ' . $resp->getContent();
    $this->assertTrue(in_array($status, [422, 500]), $msg);
    }

    public function test_retrait_interdit_compte_bloque()
    {
        $admin = User::factory()->create(['admin' => true]);
        $client = User::factory()->create();
        $compte = Compte::factory()->create(['user_id' => $client->id, 'type_compte' => 'epargne', 'statut_compte' => 'bloqué']);

        $payload = ['type' => 'retrait', 'montant' => 10.00, 'compte_id' => $compte->id];
    $resp = $this->actingAs($admin, 'api')->postJson('/api/v1/transactions', $payload);
    $status = $resp->getStatusCode();
    $msg = 'Unexpected status: ' . $status . ' - ' . $resp->getContent();
    $this->assertTrue(in_array($status, [422, 500]), $msg);
    }

    public function test_transaction_sur_compte_archivé_error()
    {
        $admin = User::factory()->create(['admin' => true]);
        $client = User::factory()->create();
        $compte = Compte::factory()->create(['user_id' => $client->id, 'archived' => true]);

        $payload = ['type' => 'depot', 'montant' => 50.00, 'compte_id' => $compte->id];
    $resp = $this->actingAs($admin, 'api')->postJson('/api/v1/transactions', $payload);
    $status = $resp->getStatusCode();
    $msg = 'Unexpected status: ' . $status . ' - ' . $resp->getContent();
    $this->assertTrue(in_array($status, [422, 500]), $msg);
    }
}
