<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthTokenTest extends TestCase
{
    use RefreshDatabase;

    private function cookieName(): string
    {
        return config('mindcat.auth.refresh_cookie');
    }

    private function login(User $user): TestResponse
    {
        return $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'Password123',
        ]);
    }

    private function refreshTokenFrom(TestResponse $response): string
    {
        $cookie = $response->getCookie($this->cookieName(), false);

        $this->assertNotNull($cookie, 'A resposta não plantou o cookie de refresh.');

        return $cookie->getValue();
    }

    private function refreshWith(?string $token): TestResponse
    {
        return $this->call(
            'POST',
            '/api/refresh',
            [],
            $token === null ? [] : [$this->cookieName() => $token],
            [],
            ['HTTP_ACCEPT' => 'application/json']
        );
    }

    private function withAccess(string $token): self
    {
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    public function test_login_returns_access_token_and_refresh_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->login($user);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'user', 'token', 'expires_in'])
            ->assertJsonPath('expires_in', 1800);

        $this->assertNotEmpty($this->refreshTokenFrom($response));
    }

    public function test_refresh_cookie_is_http_only_and_scoped_to_the_refresh_route(): void
    {
        $user = User::factory()->create();

        $cookie = $this->login($user)->getCookie($this->cookieName(), false);

        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', strtolower((string) $cookie->getSameSite()));
        $this->assertSame(config('mindcat.auth.refresh_cookie_path'), $cookie->getPath());
    }

    public function test_access_token_authenticates_protected_route(): void
    {
        $user = User::factory()->create();

        $access = $this->login($user)->json('token');

        $this->withAccess($access)->getJson('/api/me')->assertStatus(200);
    }

    public function test_refresh_token_cannot_authenticate_protected_routes(): void
    {
        $user = User::factory()->create();

        $refresh = $this->refreshTokenFrom($this->login($user));

        $this->withAccess($refresh)->getJson('/api/me')->assertStatus(401);
    }

    public function test_refresh_issues_a_new_pair(): void
    {
        $user = User::factory()->create();

        $refresh = $this->refreshTokenFrom($this->login($user));

        $response = $this->refreshWith($refresh);

        $response->assertStatus(200)->assertJsonStructure(['user', 'token', 'expires_in']);

        $this->assertNotEmpty($this->refreshTokenFrom($response));
    }

    public function test_refresh_revokes_the_previous_pair(): void
    {
        $user = User::factory()->create();

        $refresh = $this->refreshTokenFrom($this->login($user));

        $this->refreshWith($refresh)->assertStatus(200);

        $this->refreshWith($refresh)->assertStatus(401);
    }

    public function test_refresh_without_cookie_fails(): void
    {
        $this->refreshWith(null)->assertStatus(401);
    }

    public function test_access_token_cannot_be_used_to_refresh(): void
    {
        $user = User::factory()->create();

        $access = $this->login($user)->json('token');

        $this->refreshWith($access)->assertStatus(401);
    }

    public function test_expired_access_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $access = $this->login($user)->json('token');

        $this->travel(31)->minutes();

        $this->withAccess($access)->getJson('/api/me')->assertStatus(401);
    }

    public function test_expired_refresh_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $refresh = $this->refreshTokenFrom($this->login($user));

        $this->travel(config('mindcat.auth.refresh_ttl_days') + 1)->days();

        $this->refreshWith($refresh)->assertStatus(401);
    }

    public function test_logout_revokes_the_session_pair(): void
    {
        $user = User::factory()->create();

        $access = $this->login($user)->json('token');

        $this->withAccess($access)->postJson('/api/logout')->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_preserves_other_sessions(): void
    {
        $user = User::factory()->create();

        $first = $this->login($user)->json('token');
        $this->login($user);

        $this->withAccess($first)->postJson('/api/logout')->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 2);
    }

    public function test_tokens_are_issued_with_separate_abilities(): void
    {
        $user = User::factory()->create();

        $this->login($user);

        $abilities = $user->tokens()->pluck('abilities');

        $this->assertCount(2, $abilities);
        $this->assertTrue($abilities->contains([AuthService::ABILITY_ACCESS]));
        $this->assertTrue($abilities->contains([AuthService::ABILITY_REFRESH]));
    }
}