<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMoodTracking;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoodTest extends TestCase
{
    use RefreshDatabase;

    // ─── STORE ───

    /** @test */
    public function user_can_register_mood(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/moods', [
            'mood_level' => 4,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_mood_tracking', [
            'user_id'    => $user->id,
            'mood_level' => 4,
        ]);
    }

    /** @test */
    public function user_can_register_mood_with_description(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/moods', [
            'mood_level'       => 5,
            'mood_description' => 'Dia incrível!',
        ]);

        $response->assertStatus(201)
            ->assertJson(['mood_description' => 'Dia incrível!']);
    }

    /** @test */
    public function user_cannot_register_mood_twice_same_day(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/moods', ['mood_level' => 3]);

        $response = $this->actingAs($user)->postJson('/api/moods', [
            'mood_level' => 5,
        ]);

        $response->assertStatus(409);
    }

    /** @test */
    public function mood_level_must_be_between_1_and_5(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/moods', ['mood_level' => 0])->assertStatus(422);
        $this->actingAs($user)->postJson('/api/moods', ['mood_level' => 6])->assertStatus(422);
        $this->actingAs($user)->postJson('/api/moods', ['mood_level' => -1])->assertStatus(422);
    }

    /** @test */
    public function mood_level_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/moods', [])->assertStatus(422);
    }

    // ─── INDEX ───

    /** @test */
    public function user_can_list_moods(): void
    {
        $user = User::factory()->create();

        UserMoodTracking::create([
            'user_id'     => $user->id,
            'mood_level'  => 3,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/moods');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function user_can_filter_moods_by_date(): void
    {
        $user = User::factory()->create();

        UserMoodTracking::create([
            'user_id'     => $user->id,
            'mood_level'  => 3,
            'recorded_at' => Carbon::now()->subDays(10),
        ]);

        UserMoodTracking::create([
            'user_id'     => $user->id,
            'mood_level'  => 5,
            'recorded_at' => Carbon::now(),
        ]);

        $from = Carbon::now()->subDays(2)->toDateString();
        $response = $this->actingAs($user)->getJson("/api/moods?from={$from}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function user_cannot_see_other_users_moods(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserMoodTracking::create([
            'user_id'     => $user2->id,
            'mood_level'  => 5,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user1)->getJson('/api/moods');
        $this->assertCount(0, $response->json('data'));
    }

    // ─── DESTROY ───

    /** @test */
    public function user_can_delete_own_mood(): void
    {
        $user = User::factory()->create();
        $mood = UserMoodTracking::create([
            'user_id'     => $user->id,
            'mood_level'  => 3,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/moods/{$mood->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('user_mood_tracking', ['id' => $mood->id]);
    }

    /** @test */
    public function user_cannot_delete_other_users_mood(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $mood = UserMoodTracking::create([
            'user_id'     => $user2->id,
            'mood_level'  => 3,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user1)->deleteJson("/api/moods/{$mood->id}");
        $response->assertStatus(404);
    }

    /** @test */
    public function moods_require_authentication(): void
    {
        $this->postJson('/api/moods', ['mood_level' => 3])->assertStatus(401);
        $this->getJson('/api/moods')->assertStatus(401);
    }
}
