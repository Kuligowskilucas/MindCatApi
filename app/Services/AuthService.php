<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Exceptions\EmailNotVerifiedException;
use Illuminate\Auth\Events\Registered;

class AuthService
{
    public const ABILITY_ACCESS  = 'access';
    public const ABILITY_REFRESH = 'refresh';

    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => $data['role'] ?? 'patient',
        ]);

        event(new Registered($user));

        return [
            'user' => $this->formatUser($user),
        ];
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email e/ou Senha incorretos.'],
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            throw new EmailNotVerifiedException();
        }

        return [
            'user'   => $this->formatUser($user),
            'tokens' => $this->issue($user),
        ];
    }

    /**
     * Troca um refresh token válido por um par novo, revogando o anterior.
     * O par antigo morre junto: rotação completa a cada renovação.
     */
    public function refresh(?string $plainRefreshToken): array
    {
        $token = $plainRefreshToken
            ? PersonalAccessToken::findToken($plainRefreshToken)
            : null;

        if (!$token || !$token->can(self::ABILITY_REFRESH)) {
            throw new HttpException(401, 'Sua sessão expirou. Entre novamente.');
        }

        if ($token->expires_at && now()->greaterThan($token->expires_at)) {
            $token->delete();

            throw new HttpException(401, 'Sua sessão expirou. Entre novamente.');
        }

        $user = $token->tokenable;

        if (!$user instanceof User) {
            $token->delete();

            throw new HttpException(401, 'Sua sessão expirou. Entre novamente.');
        }

        $session = $token->name;
        $this->revokeSession($user, $session);

        return [
            'user'   => $this->formatUser($user),
            'tokens' => $this->issue($user, $session),
        ];
    }

    /** Derruba só o par da sessão atual, preservando outros dispositivos. */
    public function logout(User $user): void
    {
        $current = $user->currentAccessToken();

        if ($current instanceof PersonalAccessToken) {
            $this->revokeSession($user, $current->name);

            return;
        }

        $user->tokens()->delete();
    }

    /**
     * Emite o par access + refresh sob um mesmo nome de sessão, que é o que
     * permite revogar os dois juntos no logout e na rotação.
     */
    private function issue(User $user, ?string $session = null): array
    {
        $session ??= 'web:' . Str::uuid()->toString();

        $accessMinutes = (int) config('mindcat.auth.access_ttl_minutes');
        $refreshDays   = (int) config('mindcat.auth.refresh_ttl_days');

        $access = $user->createToken(
            $session,
            [self::ABILITY_ACCESS],
            now()->addMinutes($accessMinutes)
        );

        $refresh = $user->createToken(
            $session,
            [self::ABILITY_REFRESH],
            now()->addDays($refreshDays)
        );

        return [
            'access'     => $access->plainTextToken,
            'refresh'    => $refresh->plainTextToken,
            'expires_in' => $accessMinutes * 60,
        ];
    }

    private function revokeSession(User $user, string $session): void
    {
        $user->tokens()->where('name', $session)->delete();
    }

    private function formatUser(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ];
    }
}