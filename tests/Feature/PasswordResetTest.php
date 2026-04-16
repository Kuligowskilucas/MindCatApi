<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    // ─── SEND CODE ───

    /** @test */
    public function send_code_returns_success_for_existing_email(): void
    {
        User::factory()->create(['email' => 'existe@teste.com']);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'existe@teste.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('password_reset_codes', ['email' => 'existe@teste.com']);
    }

    /** @test */
    public function send_code_returns_same_response_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'naoexiste@teste.com',
        ]);

        // Não deve revelar se o email existe
        $response->assertStatus(200);
        $this->assertDatabaseMissing('password_reset_codes', ['email' => 'naoexiste@teste.com']);
    }

    /** @test */
    public function send_code_requires_valid_email(): void
    {
        $this->postJson('/api/forgot-password', ['email' => ''])->assertStatus(422);
        $this->postJson('/api/forgot-password', ['email' => 'invalido'])->assertStatus(422);
    }

    /** @test */
    public function send_code_replaces_old_codes(): void
    {
        $user = User::factory()->create(['email' => 'teste@teste.com']);

        $this->postJson('/api/forgot-password', ['email' => 'teste@teste.com']);
        $this->postJson('/api/forgot-password', ['email' => 'teste@teste.com']);

        $count = DB::table('password_reset_codes')->where('email', 'teste@teste.com')->count();
        $this->assertEquals(1, $count);
    }

    // ─── RESET PASSWORD ───

    /** @test */
    public function user_can_reset_password_with_valid_code(): void
    {
        $user = User::factory()->create(['email' => 'teste@teste.com']);

        $code = '123456';
        DB::table('password_reset_codes')->insert([
            'email'      => 'teste@teste.com',
            'code'       => Hash::make($code),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'    => 'teste@teste.com',
            'code'     => $code,
            'password' => 'NovaSenha123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Senha redefinida com sucesso!']);

        // Verifica que a senha mudou
        $user->refresh();
        $this->assertTrue(Hash::check('NovaSenha123', $user->password));

        // Verifica que o código foi removido
        $this->assertDatabaseMissing('password_reset_codes', ['email' => 'teste@teste.com']);
    }

    /** @test */
    public function reset_fails_with_wrong_code(): void
    {
        $user = User::factory()->create(['email' => 'teste@teste.com']);

        DB::table('password_reset_codes')->insert([
            'email'      => 'teste@teste.com',
            'code'       => Hash::make('123456'),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'    => 'teste@teste.com',
            'code'     => '999999',
            'password' => 'NovaSenha123',
        ]);

        $response->assertStatus(422);

        // Verifica que attempts foi incrementado
        $record = DB::table('password_reset_codes')->where('email', 'teste@teste.com')->first();
        $this->assertEquals(1, $record->attempts);
    }

    /** @test */
    public function reset_blocks_after_max_attempts(): void
    {
        $user = User::factory()->create(['email' => 'teste@teste.com']);

        DB::table('password_reset_codes')->insert([
            'email'      => 'teste@teste.com',
            'code'       => Hash::make('123456'),
            'attempts'   => 5,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'    => 'teste@teste.com',
            'code'     => '123456',
            'password' => 'NovaSenha123',
        ]);

        $response->assertStatus(429);
        $this->assertDatabaseMissing('password_reset_codes', ['email' => 'teste@teste.com']);
    }

    /** @test */
    public function reset_fails_with_expired_code(): void
    {
        $user = User::factory()->create(['email' => 'teste@teste.com']);

        DB::table('password_reset_codes')->insert([
            'email'      => 'teste@teste.com',
            'code'       => Hash::make('123456'),
            'attempts'   => 0,
            'expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'    => 'teste@teste.com',
            'code'     => '123456',
            'password' => 'NovaSenha123',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function reset_validates_input(): void
    {
        $this->postJson('/api/reset-password', [])->assertStatus(422);

        $this->postJson('/api/reset-password', [
            'email'    => 'teste@teste.com',
            'code'     => '12',
            'password' => 'NovaSenha123',
        ])->assertStatus(422);
    }
}
