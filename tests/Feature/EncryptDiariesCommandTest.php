<?php

namespace Tests\Feature;

use App\Models\DiaryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptDiariesCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Insere uma linha legada "de verdade": cifrada com a APP_KEY (como o cast
     * nativo fazia) e version=0, driblando o model/hook via insert cru.
     */
    private function insertLegacyEntry(int $userId, string $plain): int
    {
        return DB::table('diary_entries')->insertGetId([
            'user_id'            => $userId,
            'content'            => Crypt::encryptString($plain),
            'encryption_version' => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_migrates_legacy_v0_rows_to_dedicated_v1(): void
    {
        config(['mindcat.diary.bridge_app_key' => true]);

        $user = User::factory()->create();
        $legacyCipher = Crypt::encryptString('conteúdo legado');

        $id = DB::table('diary_entries')->insertGetId([
            'user_id'            => $user->id,
            'content'            => $legacyCipher,
            'encryption_version' => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->artisan('mindcat:encrypt-diaries')->assertExitCode(0);

        $row = DB::table('diary_entries')->where('id', $id)->first();
        $this->assertEquals(1, $row->encryption_version);
        $this->assertNotEquals($legacyCipher, $row->content);
        $this->assertEquals('conteúdo legado', DiaryEntry::find($id)->content);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_is_idempotent(): void
    {
        config(['mindcat.diary.bridge_app_key' => true]);
        $user = User::factory()->create();
        $this->insertLegacyEntry($user->id, 'a');

        $this->artisan('mindcat:encrypt-diaries')->assertExitCode(0);
        $this->assertEquals(0, DB::table('diary_entries')->where('encryption_version', 0)->count());

        $this->artisan('mindcat:encrypt-diaries')->assertExitCode(0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dry_run_changes_nothing(): void
    {
        config(['mindcat.diary.bridge_app_key' => true]);
        $user = User::factory()->create();
        $id = $this->insertLegacyEntry($user->id, 'x');

        $this->artisan('mindcat:encrypt-diaries', ['--dry-run' => true])->assertExitCode(0);

        $this->assertEquals(0, DB::table('diary_entries')->where('id', $id)->value('encryption_version'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function corrupt_row_is_skipped_and_good_row_still_migrates(): void
    {
        config(['mindcat.diary.bridge_app_key' => true]);
        $user = User::factory()->create();

        $goodId = $this->insertLegacyEntry($user->id, 'ok');
        $badId  = DB::table('diary_entries')->insertGetId([
            'user_id'            => $user->id,
            'content'            => 'isto-nao-e-cifra-valida',
            'encryption_version' => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->artisan('mindcat:encrypt-diaries')->assertExitCode(1);

        $this->assertEquals(1, DB::table('diary_entries')->where('id', $goodId)->value('encryption_version'));
        $this->assertEquals(0, DB::table('diary_entries')->where('id', $badId)->value('encryption_version'));
    }
}