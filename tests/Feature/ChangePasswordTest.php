<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_password_after_activation()
    {
        $user = User::factory()->create(['password' => bcrypt('oldPass123')]);

        $this->actingAs($user, 'api');

        $resp = $this->postJson('/api/v1/clients/change-password', [
            'current_password' => 'oldPass123',
            'new_password' => 'newPassword123',
            'new_password_confirmation' => 'newPassword123'
        ]);

        $resp->assertStatus(200);
    }
}
