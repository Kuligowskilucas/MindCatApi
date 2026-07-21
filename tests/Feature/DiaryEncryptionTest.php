<?php

namespace Tests\Feature;

use App\Models\DiaryEntry;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DiaryEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private function freshKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function stored_content_is_not_plaintext(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::create(['user_id' => $user->id, 'content' => 'segredo íntimo']);

        $raw = DB::table('diary_entries')->where('id', $entry->id)->value('content');

        $this->assertStringNotContainsString('segredo íntimo', $raw);

        $payload = json_decode(base64_decode($raw), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('iv', $payload);
        $this->assertArrayHasKey('mac', $payload);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function content_round_trips_and_marks_version_one(): void
    {
        $user = User::factory()->create();
        $entry = DiaryEntry::create(['user_id' => $user->id, 'content' => 'texto original']);

        $fresh = $entry->fresh();
        $this->assertEquals('texto original', $fresh->content);
        $this->assertEquals(1, $fresh->encryption_version);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function read_falls_back_to_previous_dedicated_key_after_rotation(): void
    {
        $user = User::factory()->create();
        $keyA = $this->freshKey();
        $keyB = $this->freshKey();

        config([
            'mindcat.diary.key'            => $keyA,
            'mindcat.diary.previous_keys'  => [],
            'mindcat.diary.bridge_app_key' => false,
        ]);
        $entry = DiaryEntry::create(['user_id' => $user->id, 'content' => 'antes da rotação']);

        config([
            'mindcat.diary.key'           => $keyB,
            'mindcat.diary.previous_keys' => [$keyA],
        ]);

        $this->assertEquals('antes da rotação', DiaryEntry::find($entry->id)->content);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function read_fails_when_no_key_can_decrypt(): void
    {
        $user = User::factory()->create();
        $keyA = $this->freshKey();
        $keyB = $this->freshKey();

        config([
            'mindcat.diary.key'            => $keyA,
            'mindcat.diary.previous_keys'  => [],
            'mindcat.diary.bridge_app_key' => false,
        ]);
        $entry = DiaryEntry::create(['user_id' => $user->id, 'content' => 'sem fallback']);

        config([
            'mindcat.diary.key'           => $keyB,
            'mindcat.diary.previous_keys' => [],
        ]);

        $this->expectException(DecryptException::class);
        DiaryEntry::find($entry->id)->content;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function legacy_v0_row_reads_through_app_key_bridge(): void
    {
        config(['mindcat.diary.bridge_app_key' => true]);
        $user = User::factory()->create();

        $id = DB::table('diary_entries')->insertGetId([
            'user_id'            => $user->id,
            'content'            => Crypt::encryptString('legado'),
            'encryption_version' => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->assertEquals('legado', DiaryEntry::find($id)->content);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function legacy_v0_row_is_unreadable_without_bridge(): void
    {
        config(['mindcat.diary.bridge_app_key' => false]);
        $user = User::factory()->create();

        $id = DB::table('diary_entries')->insertGetId([
            'user_id'            => $user->id,
            'content'            => Crypt::encryptString('legado'),
            'encryption_version' => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->expectException(DecryptException::class);
        DiaryEntry::find($id)->content;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function diary_list_degrades_corrupt_entry_instead_of_failing(): void
    {
        $user = User::factory()->patient()->create();
        $this->giveDiaryPassword($user);

        DiaryEntry::create(['user_id' => $user->id, 'content' => 'entrada boa']);
        DB::table('diary_entries')->insert([
            'user_id'            => $user->id,
            'content'            => 'payload-invalido',
            'encryption_version' => 1,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        Log::spy();

        $response = $this->actingAs($user)->postJson('/api/diary/list', [
            'diary_password' => 'Diario123',
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());

        $contents = collect($response->json())->pluck('content');
        $this->assertTrue($contents->contains('entrada boa'));
        $this->assertTrue($contents->contains(null));

        Log::shouldHaveReceived('warning')->atLeast()->once();
    }
}