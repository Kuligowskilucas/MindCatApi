<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PruneEphemeralTest extends TestCase
{
    use RefreshDatabase;

    private function otp(string $challenge, string $expiresAt): void
    {
        DB::table('login_otp_codes')->insert([
            'user_id'    => User::factory()->create()->id,
            'challenge'  => $challenge,
            'code'       => 'hash',
            'attempts'   => 0,
            'expires_at' => $expiresAt,
            'created_at' => now()->subDay(),
        ]);
    }

    private function resetCode(string $email, string $expiresAt): void
    {
        DB::table('password_reset_codes')->insert([
            'email'      => $email,
            'code'       => '123456',
            'expires_at' => $expiresAt,
            'created_at' => now()->subDay(),
        ]);
    }

    public function test_prunes_expired_rows_and_keeps_fresh_ones(): void
    {
        $this->otp('otp-expirado', now()->subHour());
        $this->otp('otp-valido', now()->addHour());
        $this->resetCode('expirado@x.com', now()->subHour());
        $this->resetCode('valido@x.com', now()->addHour());

        $this->artisan('mindcat:prune-ephemeral')->assertSuccessful();

        $this->assertDatabaseMissing('login_otp_codes', ['challenge' => 'otp-expirado']);
        $this->assertDatabaseHas('login_otp_codes', ['challenge' => 'otp-valido']);
        $this->assertDatabaseMissing('password_reset_codes', ['email' => 'expirado@x.com']);
        $this->assertDatabaseHas('password_reset_codes', ['email' => 'valido@x.com']);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $this->otp('otp-expirado', now()->subHour());
        $this->resetCode('expirado@x.com', now()->subHour());

        $this->artisan('mindcat:prune-ephemeral', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('login_otp_codes', ['challenge' => 'otp-expirado']);
        $this->assertDatabaseHas('password_reset_codes', ['email' => 'expirado@x.com']);
    }
}
