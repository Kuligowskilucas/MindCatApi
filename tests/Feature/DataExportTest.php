<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMoodTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_includes_profile_moods_tasks_and_decrypted_diary(): void
    {
        $user = User::factory()->create();
        $this->giveDiaryPassword($user);

        UserMoodTracking::create([
            'user_id'          => $user->id,
            'mood_level'       => 4,
            'mood_description' => 'dia ok',
            'recorded_at'      => now(),
        ]);

        $user->diaryEntries()->create(['content' => 'querido diário']);

        $response = $this->actingAs($user)->postJson('/api/user/export', [
            'diary_password' => 'Diario123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('moods.0.mood_level', 4)
            ->assertJsonPath('moods.0.mood_description', 'dia ok')
            ->assertJsonPath('diary.0.content', 'querido diário');
    }

    public function test_export_rejects_wrong_diary_password(): void
    {
        $user = User::factory()->create();
        $this->giveDiaryPassword($user);
        $user->diaryEntries()->create(['content' => 'secreto']);

        $this->actingAs($user)->postJson('/api/user/export', [
            'diary_password' => 'errada',
        ])->assertStatus(403);
    }

    public function test_export_works_without_diary_for_user_with_no_diary_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/user/export', [])
            ->assertStatus(200)
            ->assertJsonPath('diary', []);
    }

    public function test_export_requires_authentication(): void
    {
        $this->postJson('/api/user/export', [])->assertStatus(401);
    }
}
