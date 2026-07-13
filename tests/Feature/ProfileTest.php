<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    // ─── SHOW ───

    /** @test */
    public function show_creates_profile_if_not_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id]);
    }

    /** @test */
    public function show_returns_existing_profile(): void
    {
        $user = User::factory()->create();
        UserProfile::create([
            'user_id'                         => $user->id,
            'consent_share_with_professional' => true,
            'push_notifications'              => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJson(['consent_share_with_professional' => true]);
    }

    // ─── UPDATE ───

    /** @test */
    public function user_can_toggle_consent(): void
    {
        $user = User::factory()->create();
        UserProfile::create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson('/api/profile', [
            'consent_share_with_professional' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson(['consent_share_with_professional' => true]);
    }

    /** @test */
    public function user_can_disable_consent(): void
    {
        $user = User::factory()->create();
        UserProfile::create([
            'user_id'                         => $user->id,
            'consent_share_with_professional' => true,
        ]);

        $response = $this->actingAs($user)->putJson('/api/profile', [
            'consent_share_with_professional' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson(['consent_share_with_professional' => false]);
    }

    // ─── SET DIARY PASSWORD ───

    /** @test */
    public function user_can_set_diary_password_first_time(): void
    {
        $user = User::factory()->create();   // sem perfil

        $response = $this->actingAs($user)->putJson('/api/profile/diary-password', [
            'new_password' => 'Diario123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Senha do diário atualizada com sucesso.']);
    }

    /** @test */
    public function user_can_change_diary_password_with_current(): void
    {
        $user = User::factory()->create();
        $this->giveDiaryPassword($user, 'SenhaAntiga1');

        $response = $this->actingAs($user)->putJson('/api/profile/diary-password', [
            'current_password' => 'SenhaAntiga1',
            'new_password'     => 'SenhaNova1',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function change_diary_password_fails_with_wrong_current(): void
    {
        $user = User::factory()->create();
        $this->giveDiaryPassword($user, 'SenhaAntiga1');

        $response = $this->actingAs($user)->putJson('/api/profile/diary-password', [
            'current_password' => 'ErradaTotal1',
            'new_password'     => 'SenhaNova1',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function diary_password_must_be_strong(): void
    {
        $user = User::factory()->create();
        UserProfile::create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson('/api/profile/diary-password', [
            'new_password' => '1234',
        ]);

        $response->assertStatus(422);
    }
}