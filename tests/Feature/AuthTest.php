<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── REGISTER ───

    /** @test */
    public function user_can_register_as_patient(): void
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Novo Paciente',
            'email'    => 'paciente@teste.com',
            'password' => 'senha123',
            'role'     => 'patient',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'user', 'token'])
            ->assertJson(['user' => ['role' => 'patient']]);

        $this->assertDatabaseHas('users', [
            'email' => 'paciente@teste.com',
            'role'  => 'patient',
        ]);
    }

    /** @test */
    public function user_can_register_as_pro(): void
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Dr. Teste',
            'email'    => 'pro@teste.com',
            'password' => 'senha123',
            'role'     => 'pro',
        ]);

        $response->assertStatus(201)
            ->assertJson(['user' => ['role' => 'pro']]);
    }

    /** @test */
    public function register_defaults_to_patient_when_no_role(): void
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Sem Role',
            'email'    => 'semrole@teste.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(201)
            ->assertJson(['user' => ['role' => 'patient']]);
    }

    /** @test */
    public function register_rejects_invalid_role(): void
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Hacker',
            'email'    => 'hack@teste.com',
            'password' => 'senha123',
            'role'     => 'admin',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function register_requires_name_email_password(): void
    {
        $this->postJson('/api/register', [])->assertStatus(422);
        $this->postJson('/api/register', ['name' => 'A'])->assertStatus(422);
        $this->postJson('/api/register', ['name' => 'A', 'email' => 'a@b.com'])->assertStatus(422);
    }

    /** @test */
    public function register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'usado@teste.com']);

        $response = $this->postJson('/api/register', [
            'name'     => 'Outro',
            'email'    => 'usado@teste.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function register_rejects_short_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Teste',
            'email'    => 'teste@teste.com',
            'password' => '123',
        ]);

        $response->assertStatus(422);
    }

    // ─── LOGIN ───

    /** @test */
    public function user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('senha123')]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'senha123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'user', 'token']);
    }

    /** @test */
    public function login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('senha123')]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'senhaerrada',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'naoexiste@teste.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(422);
    }

    // ─── LOGOUT ───

    /** @test */
    public function user_can_logout(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout realizado com sucesso!']);
    }

    /** @test */
    public function logout_requires_authentication(): void
    {
        $this->postJson('/api/logout')->assertStatus(401);
    }

    // ─── USER PROFILE ───

    /** @test */
    public function authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson(['id' => $user->id, 'email' => $user->email]);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_profile(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }
}
