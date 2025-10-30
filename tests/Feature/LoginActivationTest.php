<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class LoginActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_activation_code_sets_must_change_password()
    {
        $user = User::factory()->create([
            'email' => 'act@test.com',
            'activation_code' => '123456',
            'activation_expires_at' => now()->addHour(),
        ]);

        // Ensure a personal access oauth client exists for Passport during tests
        \Illuminate\Support\Facades\DB::table('oauth_clients')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'owner_type' => null,
            'owner_id' => null,
            'name' => 'Test Personal',
            'secret' => null,
            'provider' => 'users',
            'redirect_uris' => json_encode([]),
            'grant_types' => json_encode(['personal_access']),
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resp = $this->postJson('/api/v1/login', [
            'identifier' => 'act@test.com',
            'activation_code' => '123456'
        ]);

        $resp->assertStatus(200);
        $resp->assertJsonStructure(['access_token','token_type','must_change_password']);
        $this->assertTrue($resp->json('must_change_password'));
    }
}
