<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    private function seedChallenge(User $user, string $code, ?callable $tweak = null): string
    {
        $challenge = 'chal-' . $user->id;

        $row = [
            'user_id'    => $user->id,
            'challenge'  => $challenge,
            'code'       => Hash::make($code),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(10),
        ];

        if ($tweak) {
            $row = $tweak($row);
        }

        DB::table('login_otp_codes')->insert($row);

        return $challenge;
    }

    public function test_login_without_2fa_returns_token_directly(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Senha123')]);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'Senha123'])
            ->assertStatus(200)
            ->assertJsonStructure(['message', 'user', 'token'])
            ->assertJsonMissingPath('two_factor_required');
    }

    public function test_login_with_2fa_enabled_returns_challenge_and_no_token(): void
    {
        $user = User::factory()->twoFactor()->create(['password' => bcrypt('Senha123')]);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'Senha123'])
            ->assertStatus(200)
            ->assertJsonPath('two_factor_required', true)
            ->assertJsonStructure(['two_factor_required', 'challenge', 'message'])
            ->assertJsonMissingPath('token');

        $this->assertDatabaseHas('login_otp_codes', ['user_id' => $user->id]);
    }

    public function test_verify_otp_with_correct_code_issues_tokens(): void
    {
        $user = User::factory()->twoFactor()->create();
        $challenge = $this->seedChallenge($user, '123456');

        $this->postJson('/api/login/verify-otp', ['challenge' => $challenge, 'code' => '123456'])
            ->assertStatus(200)
            ->assertJsonStructure(['message', 'user', 'token']);

        $this->assertDatabaseMissing('login_otp_codes', ['challenge' => $challenge]);
    }

    public function test_verify_otp_with_wrong_code_increments_attempts(): void
    {
        $user = User::factory()->twoFactor()->create();
        $challenge = $this->seedChallenge($user, '123456');

        $this->postJson('/api/login/verify-otp', ['challenge' => $challenge, 'code' => '000000'])
            ->assertStatus(422);

        $this->assertDatabaseHas('login_otp_codes', ['challenge' => $challenge, 'attempts' => 1]);
    }

    public function test_verify_otp_rejects_expired_challenge(): void
    {
        $user = User::factory()->twoFactor()->create();
        $challenge = $this->seedChallenge($user, '123456', function (array $row) {
            $row['expires_at'] = now()->subMinute();
            return $row;
        });

        $this->postJson('/api/login/verify-otp', ['challenge' => $challenge, 'code' => '123456'])
            ->assertStatus(422);

        $this->assertDatabaseMissing('login_otp_codes', ['challenge' => $challenge]);
    }

    public function test_verify_otp_blocks_after_max_attempts(): void
    {
        $user = User::factory()->twoFactor()->create();
        $challenge = $this->seedChallenge($user, '123456', function (array $row) {
            $row['attempts'] = 5;
            return $row;
        });

        $this->postJson('/api/login/verify-otp', ['challenge' => $challenge, 'code' => '123456'])
            ->assertStatus(429);
    }

    public function test_verify_otp_validates_code_format(): void
    {
        $this->postJson('/api/login/verify-otp', ['challenge' => 'x', 'code' => '12'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_resend_otp_refreshes_code_and_resets_attempts(): void
    {
        $user = User::factory()->twoFactor()->create();
        $challenge = $this->seedChallenge($user, '123456', function (array $row) {
            $row['attempts'] = 3;
            return $row;
        });

        $before = DB::table('login_otp_codes')->where('challenge', $challenge)->value('code');

        $this->postJson('/api/login/resend-otp', ['challenge' => $challenge])
            ->assertStatus(200);

        $row = DB::table('login_otp_codes')->where('challenge', $challenge)->first();
        $this->assertSame(0, (int) $row->attempts);
        $this->assertNotSame($before, $row->code);
    }

    public function test_toggle_two_factor_requires_correct_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Senha123')]);

        $this->actingAs($user)
            ->putJson('/api/profile/two-factor', ['enabled' => true, 'password' => 'errada'])
            ->assertStatus(403);

        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    public function test_user_can_enable_and_disable_two_factor(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Senha123')]);

        $this->actingAs($user)
            ->putJson('/api/profile/two-factor', ['enabled' => true, 'password' => 'Senha123'])
            ->assertStatus(200)
            ->assertJsonPath('two_factor_enabled', true);

        $this->assertTrue($user->fresh()->two_factor_enabled);

        $this->actingAs($user->fresh())
            ->putJson('/api/profile/two-factor', ['enabled' => false, 'password' => 'Senha123'])
            ->assertStatus(200)
            ->assertJsonPath('two_factor_enabled', false);

        $this->assertFalse($user->fresh()->two_factor_enabled);
    }
}