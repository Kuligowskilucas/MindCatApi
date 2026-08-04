<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function signedVerifyUrl(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => $hash ?? sha1($user->getEmailForVerification()),
            ]
        );
    }

    // ─── REGISTRO ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function register_creates_unverified_user_without_issuing_a_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'name'     => 'Novo Paciente',
            'email'    => 'novo@teste.com',
            'password' => 'Senha123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'user'])
            ->assertJsonMissingPath('token');

        $user = User::where('email', 'novo@teste.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function register_sends_the_verification_email(): void
    {
        Notification::fake();

        $this->postJson('/api/register', [
            'name'     => 'Novo Paciente',
            'email'    => 'novo@teste.com',
            'password' => 'Senha123',
        ])->assertStatus(201);

        $user = User::where('email', 'novo@teste.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    // ─── LOGIN BARRADO ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function unverified_user_cannot_login(): void
    {
        $user = User::factory()->unverified()->create(['password' => bcrypt('Senha123')]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'Senha123',
        ])
            ->assertStatus(403)
            ->assertJson(['code' => 'email_not_verified']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function verified_user_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Senha123')]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'Senha123',
        ])
            ->assertStatus(200)
            ->assertJsonStructure(['message', 'user', 'token']);
    }

    // ─── VERIFICAÇÃO ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function valid_signed_link_verifies_the_email_and_fires_event(): void
    {
        Event::fake([Verified::class]);
        $user = User::factory()->unverified()->create();

        $this->getJson($this->signedVerifyUrl($user))
            ->assertOk()
            ->assertJson(['message' => 'E-mail confirmado com sucesso.']);

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function already_verified_link_is_idempotent(): void
    {
        $user = User::factory()->create();

        $this->getJson($this->signedVerifyUrl($user))
            ->assertOk()
            ->assertJson(['already_verified' => true]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function wrong_hash_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson($this->signedVerifyUrl($user, 'hasherrado'))
            ->assertStatus(403);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function expired_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $expired = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->getJson($expired)->assertStatus(403);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function tampered_signature_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson($this->signedVerifyUrl($user) . 'x')->assertStatus(403);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    // ─── REENVIO ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function resend_sends_notification_for_a_pending_account(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->postJson('/api/email/verification-notification', ['email' => $user->email])
            ->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resend_does_nothing_for_an_already_verified_account(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/email/verification-notification', ['email' => $user->email])
            ->assertOk();

        Notification::assertNothingSent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resend_does_not_reveal_whether_an_email_exists(): void
    {
        Notification::fake();

        $this->postJson('/api/email/verification-notification', ['email' => 'ninguem@teste.com'])
            ->assertOk();

        Notification::assertNothingSent();
    }
}