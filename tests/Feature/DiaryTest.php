<?php

namespace Tests\Feature;

use App\Models\DiaryEntry;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DiaryTest extends TestCase
{
    use RefreshDatabase;

    private function createPatientWithDiaryPassword(): User
    {
        $user = User::factory()->patient()->create();
        $this->giveDiaryPassword($user);

        return $user;
    }

    // ─── STORE ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_create_diary_entry(): void
    {
        $user = User::factory()->create();
        $this->giveDiaryPassword($user);   // Diario123
    
        $response = $this->actingAs($user)->postJson('/api/diary', [
            'content'        => 'Hoje foi um bom dia.',
            'diary_password' => 'Diario123',
        ]);
    
        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'entry']);
    
        $entry = DiaryEntry::where('user_id', $user->id)->first();
        $this->assertNotNull($entry);
        $this->assertEquals('Hoje foi um bom dia.', $entry->content);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function diary_entry_requires_content(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/diary', [
            'content' => '',
        ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function diary_entry_rejects_content_over_50000_chars(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/diary', [
            'content' => str_repeat('a', 50001),
        ]);

        $response->assertStatus(422);
    }

    // ─── LIST (INDEX) ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_list_entries_with_correct_password(): void
    {
        $user = $this->createPatientWithDiaryPassword();

        DiaryEntry::create(['user_id' => $user->id, 'content' => 'Entrada 1']);
        DiaryEntry::create(['user_id' => $user->id, 'content' => 'Entrada 2']);

        $response = $this->actingAs($user)->postJson('/api/diary/list', [
            'diary_password' => 'Diario123',
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function list_fails_with_wrong_password(): void
    {
        $user = $this->createPatientWithDiaryPassword();

        $response = $this->actingAs($user)->postJson('/api/diary/list', [
            'diary_password' => 'senhaerrada',
        ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function list_fails_without_password(): void
    {
        $user = $this->createPatientWithDiaryPassword();

        $response = $this->actingAs($user)->postJson('/api/diary/list', []);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_see_other_users_entries(): void
    {
        $user1 = $this->createPatientWithDiaryPassword();
        $user2 = User::factory()->create();

        DiaryEntry::create(['user_id' => $user2->id, 'content' => 'Entrada do outro']);

        $response = $this->actingAs($user1)->postJson('/api/diary/list', [
            'diary_password' => 'Diario123',
        ]);

        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    // ─── DESTROY ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_delete_own_entry(): void
    {
        $user = $this->createPatientWithDiaryPassword();
        $entry = DiaryEntry::create(['user_id' => $user->id, 'content' => 'Deletar']);

        $response = $this->actingAs($user)->deleteJson("/api/diary/{$entry->id}", [
            'diary_password' => 'Diario123',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('diary_entries', ['id' => $entry->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function delete_fails_with_wrong_password(): void
    {
        $user = $this->createPatientWithDiaryPassword();
        $entry = DiaryEntry::create(['user_id' => $user->id, 'content' => 'Entrada']);

        $response = $this->actingAs($user)->deleteJson("/api/diary/{$entry->id}", [
            'diary_password' => 'errada',
        ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_delete_other_users_entry(): void
    {
        $user1 = $this->createPatientWithDiaryPassword();
        $user2 = User::factory()->create();
        $entry = DiaryEntry::create(['user_id' => $user2->id, 'content' => 'Do outro']);

        $response = $this->actingAs($user1)->deleteJson("/api/diary/{$entry->id}", [
            'diary_password' => 'Diario123',
        ]);

        $response->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function store_requires_authentication(): void
    {
        $this->postJson('/api/diary', ['content' => 'teste'])->assertStatus(401);
    }
}
