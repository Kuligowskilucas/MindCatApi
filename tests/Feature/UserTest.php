<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\DiaryEntry;
use App\Models\UserMoodTracking;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UserTest extends TestCase
{
    use RefreshDatabase;

    // ─── ME ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function me_returns_user_with_profile(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'email', 'role', 'profile']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function me_hides_diary_password_hash(): void
    {
        $user = User::factory()->create();
        $this->giveDiaryPassword($user, 'Segredo123');

        $response = $this->actingAs($user)->getJson('/api/me');

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('diary_password_hash', $response->json('profile'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    // ─── UPDATE ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_name(): void
    {
        $user = User::factory()->create(['name' => 'Nome Antigo']);

        $response = $this->actingAs($user)->putJson('/api/user/update', [
            'name' => 'Nome Novo',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nome Novo']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_email(): void
    {
        $user = User::factory()->create(['email' => 'antigo@teste.com']);

        $response = $this->actingAs($user)->putJson('/api/user/update', [
            'email' => 'novo@teste.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'novo@teste.com']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('SenhaAtual123')]);

        $response = $this->actingAs($user)->putJson('/api/user/update', [
            'current_password' => 'SenhaAtual123',
            'password'         => 'NovaSenha123',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue(Hash::check('NovaSenha123', $user->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_rejects_duplicate_email(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@teste.com']);
        $user2 = User::factory()->create(['email' => 'user2@teste.com']);

        $response = $this->actingAs($user1)->putJson('/api/user/update', [
            'email' => 'user2@teste.com',
        ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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
    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/api/user/delete');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Usuário deletado com sucesso!']);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function delete_removes_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('test_token');

        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);

        $this->actingAs($user)->deleteJson('/api/user/delete');

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_password_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('SenhaAtual123')]);

        $this->actingAs($user)->putJson('/api/user/update', [
            'password' => 'NovaSenha123',
        ])->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('SenhaAtual123')]);

        $this->actingAs($user)->putJson('/api/user/update', [
            'current_password' => 'SenhaErrada123',
            'password'         => 'NovaSenha123',
        ])->assertStatus(422);

        $user->refresh();
        $this->assertTrue(Hash::check('SenhaAtual123', $user->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function delete_account_erases_diary_and_moods(): void
    {
        $user = User::factory()->create();

        $entry = DiaryEntry::create(['user_id' => $user->id, 'content' => 'Íntimo']);
        $mood  = UserMoodTracking::create([
            'user_id'     => $user->id,
            'mood_level'  => 3,
            'recorded_at' => now(),
        ]);

        $this->actingAs($user)->deleteJson('/api/user/delete')->assertStatus(200);

        // Não basta soft delete: conteúdo íntimo tem que sumir do banco.
        $this->assertDatabaseMissing('diary_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('user_mood_tracking', ['id' => $mood->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function delete_account_anonymizes_user(): void
    {
        $user = User::factory()->create([
            'name'  => 'Lucas',
            'email' => 'lucas@teste.com',
        ]);

        $this->actingAs($user)->deleteJson('/api/user/delete')->assertStatus(200);

        $row = DB::table('users')->where('id', $user->id)->first();

        $this->assertSame('Usuário removido', $row->name);
        $this->assertNotSame('lucas@teste.com', $row->email);
        $this->assertNotNull($row->deleted_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function deleted_email_can_be_reused(): void
    {
        $user = User::factory()->create(['email' => 'lucas@teste.com']);

        $this->actingAs($user)->deleteJson('/api/user/delete')->assertStatus(200);

        $this->postJson('/api/register', [
            'name'     => 'Lucas de novo',
            'email'    => 'lucas@teste.com',
            'password' => 'Senha123',
        ])->assertStatus(201);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function delete_account_keeps_pro_tasks(): void
    {
        $pro     = User::factory()->pro()->create();
        $patient = User::factory()->patient()->create();

        $task = Task::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'title'      => 'Registro clínico',
            'status'     => 'active',
        ]);

        $this->actingAs($patient)->deleteJson('/api/user/delete')->assertStatus(200);

        // Registro clínico do profissional sobrevive, sem dado pessoal do paciente.
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }
}
