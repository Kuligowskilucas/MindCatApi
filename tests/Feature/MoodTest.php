<?php

namespace Tests\Feature;

use App\Models\Feeling;
use App\Models\User;
use App\Models\UserMoodTracking;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoodTest extends TestCase
{
    use RefreshDatabase;

    private function seedFeelings(): void
    {
        Feeling::firstOrCreate(['slug' => 'ansioso'], ['label' => 'Ansioso', 'sort_order' => 1]);
        Feeling::firstOrCreate(['slug' => 'calmo'], ['label' => 'Calmo', 'sort_order' => 10]);
    }

    private function payload(array $overrides = []): array
    {
        $this->seedFeelings();

        return array_merge([
            'mood_level' => 4,
            'thought'    => 'Achei que não ia dar conta da reunião.',
            'behavior'   => 'Respirei fundo e falei mesmo assim.',
            'feelings'   => ['ansioso'],
        ], $overrides);
    }

    // ─── STORE ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_register_mood(): void
    {
        $user = User::factory()->patient()->create();

        $response = $this->actingAs($user)->postJson('/api/moods', $this->payload());

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_mood_tracking', [
            'user_id'    => $user->id,
            'mood_level' => 4,
            'thought'    => 'Achei que não ia dar conta da reunião.',
            'behavior'   => 'Respirei fundo e falei mesmo assim.',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function register_returns_thought_and_behavior(): void
    {
        $user = User::factory()->patient()->create();

        $response = $this->actingAs($user)->postJson('/api/moods', $this->payload([
            'mood_level' => 5,
            'thought'    => 'Hoje eu mereço comemorar.',
            'behavior'   => 'Saí com os amigos depois do trabalho.',
        ]));

        $response->assertStatus(201)
            ->assertJson([
                'thought'  => 'Hoje eu mereço comemorar.',
                'behavior' => 'Saí com os amigos depois do trabalho.',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_register_mood_twice_same_day(): void
    {
        $user = User::factory()->patient()->create();

        $this->actingAs($user)->postJson('/api/moods', $this->payload([
            'mood_level' => 3,
            'thought'    => 'A manhã vai ser pesada.',
            'behavior'   => 'Adiei a primeira tarefa.',
        ]))->assertStatus(201);

        $this->actingAs($user)->postJson('/api/moods', $this->payload([
            'mood_level' => 5,
            'thought'    => 'No fim deu tudo certo.',
            'behavior'   => 'Terminei tudo e fui caminhar.',
        ]))->assertStatus(201);

        $this->assertSame(2, UserMoodTracking::where('user_id', $user->id)->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function thought_is_required(): void
    {
        $user = User::factory()->patient()->create();

        $payload = $this->payload();
        unset($payload['thought']);

        $this->actingAs($user)->postJson('/api/moods', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('thought');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function behavior_is_required(): void
    {
        $user = User::factory()->patient()->create();

        $payload = $this->payload();
        unset($payload['behavior']);

        $this->actingAs($user)->postJson('/api/moods', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('behavior');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function thought_and_behavior_are_limited_to_1000_characters(): void
    {
        $user = User::factory()->patient()->create();

        $this->actingAs($user)->postJson('/api/moods', $this->payload([
            'thought' => str_repeat('a', 1001),
        ]))->assertStatus(422)->assertJsonValidationErrors('thought');

        $this->actingAs($user)->postJson('/api/moods', $this->payload([
            'behavior' => str_repeat('a', 1001),
        ]))->assertStatus(422)->assertJsonValidationErrors('behavior');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function mood_level_must_be_between_1_and_5(): void
    {
        $user = User::factory()->patient()->create();

        $this->actingAs($user)->postJson('/api/moods', $this->payload(['mood_level' => 0]))->assertStatus(422);
        $this->actingAs($user)->postJson('/api/moods', $this->payload(['mood_level' => 6]))->assertStatus(422);
        $this->actingAs($user)->postJson('/api/moods', $this->payload(['mood_level' => -1]))->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function mood_level_is_required(): void
    {
        $user = User::factory()->patient()->create();

        $payload = $this->payload();
        unset($payload['mood_level']);

        $this->actingAs($user)->postJson('/api/moods', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('mood_level');
    }

    // ─── FEELINGS ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_register_mood_with_feelings_and_they_come_back_in_index(): void
    {
        $user = User::factory()->patient()->create();

        $response = $this->actingAs($user)->postJson('/api/moods', $this->payload([
            'mood_level' => 3,
            'feelings'   => ['ansioso', 'calmo'],
        ]));

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_mood_tracking', [
            'user_id'    => $user->id,
            'mood_level' => 3,
        ]);

        $index = $this->actingAs($user)->getJson('/api/moods');
        $index->assertStatus(200);

        $slugs = collect($index->json('data.0.feelings'))->pluck('slug')->sort()->values()->all();
        $this->assertEquals(['ansioso', 'calmo'], $slugs);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function registering_mood_with_unknown_feeling_slug_fails(): void
    {
        $user = User::factory()->patient()->create();

        $response = $this->actingAs($user)->postJson('/api/moods', $this->payload([
            'mood_level' => 3,
            'feelings'   => ['nao-existe'],
        ]));

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function registering_mood_with_more_than_five_feelings_fails(): void
    {
        $user = User::factory()->patient()->create();

        $slugs = [];
        for ($i = 1; $i <= 6; $i++) {
            $slug = "sentimento-{$i}";
            Feeling::create(['slug' => $slug, 'label' => $slug, 'sort_order' => $i]);
            $slugs[] = $slug;
        }

        $response = $this->actingAs($user)->postJson('/api/moods', $this->payload([
            'mood_level' => 3,
            'feelings'   => $slugs,
        ]));

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function registering_mood_without_feelings_fails(): void
    {
        $user = User::factory()->patient()->create();

        $payload = $this->payload();
        unset($payload['feelings']);

        $this->actingAs($user)->postJson('/api/moods', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('feelings');

        $this->actingAs($user)->postJson('/api/moods', $this->payload(['feelings' => []]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('feelings');
    }

    // ─── INDEX ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_list_moods(): void
    {
        $user = User::factory()->patient()->create();

        UserMoodTracking::create([
            'user_id'     => $user->id,
            'mood_level'  => 3,
            'thought'     => 'Ninguém vai notar meu esforço.',
            'behavior'    => 'Terminei o relatório mesmo assim.',
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/moods');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.0.thought', 'Ninguém vai notar meu esforço.')
            ->assertJsonPath('data.0.behavior', 'Terminei o relatório mesmo assim.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_filter_moods_by_date(): void
    {
        $user = User::factory()->patient()->create();

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function to_filter_includes_moods_recorded_later_that_day(): void
    {
        $user = User::factory()->patient()->create();

        $day = Carbon::parse('2026-08-10');

        UserMoodTracking::create([
            'user_id'     => $user->id,
            'mood_level'  => 4,
            'recorded_at' => $day->copy()->setTime(14, 30),
        ]);

        UserMoodTracking::create([
            'user_id'     => $user->id,
            'mood_level'  => 2,
            'recorded_at' => $day->copy()->addDay()->setTime(9, 0),
        ]);

        $to = $day->toDateString();
        $response = $this->actingAs($user)->getJson("/api/moods?to={$to}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_see_other_users_moods(): void
    {
        $user1 = User::factory()->patient()->create();
        $user2 = User::factory()->patient()->create();

        UserMoodTracking::create([
            'user_id'     => $user2->id,
            'mood_level'  => 5,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user1)->getJson('/api/moods');
        $this->assertCount(0, $response->json('data'));
    }

    // ─── DESTROY ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_delete_own_mood(): void
    {
        $user = User::factory()->patient()->create();
        $mood = UserMoodTracking::create([
            'user_id'     => $user->id,
            'mood_level'  => 3,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/moods/{$mood->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('user_mood_tracking', ['id' => $mood->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_cannot_delete_other_users_mood(): void
    {
        $user1 = User::factory()->patient()->create();
        $user2 = User::factory()->patient()->create();
        $mood = UserMoodTracking::create([
            'user_id'     => $user2->id,
            'mood_level'  => 3,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user1)->deleteJson("/api/moods/{$mood->id}");
        $response->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function moods_require_authentication(): void
    {
        $this->postJson('/api/moods', ['mood_level' => 3])->assertStatus(401);
        $this->getJson('/api/moods')->assertStatus(401);
    }
}
