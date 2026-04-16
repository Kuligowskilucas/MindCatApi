<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // ─── ME ───

    /** @test */
    public function me_returns_user_with_profile(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'email', 'role', 'profile']);
    }

    /** @test */
    public function me_hides_diary_password_hash(): void
    {
        $user = User::factory()->create();
        UserProfile::create([
            'user_id'             => $user->id,
            'diary_password_hash' => Hash::make('segredo'),
        ]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('diary_password_hash', $response->json('profile'));
    }

    /** @test */
    public function me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    // ─── UPDATE ───

    /** @test */
    public function user_can_update_name(): void
    {
        $user = User::factory()->create(['name' => 'Nome Antigo']);

        $response = $this->actingAs($user)->putJson('/api/user/update', [
            'name' => 'Nome Novo',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nome Novo']);
    }

    /** @test */
    public function user_can_update_email(): void
    {
        $user = User::factory()->create(['email' => 'antigo@teste.com']);

        $response = $this->actingAs($user)->putJson('/api/user/update', [
            'email' => 'novo@teste.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'novo@teste.com']);
    }

    /** @test */
    public function user_can_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/user/update', [
            'password' => 'NovaSenha123',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue(Hash::check('NovaSenha123', $user->password));
    }

    /** @test */
    public function update_rejects_duplicate_email(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@teste.com']);
        $user2 = User::factory()->create(['email' => 'user2@teste.com']);

        $response = $this->actingAs($user1)->putJson('/api/user/update', [
            'email' => 'user2@teste.com',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function update_allows_keeping_same_email(): void
    {
        $user = User::factory()->create(['email' => 'mesmo@teste.com']);

        $response = $this->actingAs($user)->putJson('/api/user/update', [
            'email' => 'mesmo@teste.com',
            'name'  => 'Novo Nome',
        ]);

        $response->assertStatus(200);
    }

    // ─── DESTROY ───

    /** @test */
    public function user_can_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/api/user/delete');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Usuário deletado com sucesso!']);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /** @test */
    public function delete_removes_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('test_token');

        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);

        $this->actingAs($user)->deleteJson('/api/user/delete');

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }
}
