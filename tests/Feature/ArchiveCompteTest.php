<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArchiveCompteTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_by_id_moves_compte_to_buffer()
    {
        $compte = Compte::factory()->create();

        $response = $this->postJson('/api/v1/comptes/' . $compte->id . '/archive');
        $response->assertStatus(200);

    $this->assertDatabaseMissing('comptes', ['id' => $compte->id]);

    }

    public function test_archive_by_numero_moves_compte_to_buffer()
    {
        $compte = Compte::factory()->create();

        $response = $this->postJson('/api/v1/comptes/' . $compte->numero_compte . '/archive');
        $response->assertStatus(200);

    $this->assertDatabaseMissing('comptes', ['numero_compte' => $compte->numero_compte]);
    }
}
