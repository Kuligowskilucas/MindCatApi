<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Conta criada. Enviamos um link de confirmação para o seu e-mail.',
            'user'    => $result['user'],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->respondWithTokens($result, 'Login realizado com sucesso!');
    }

    /** O refresh token só trafega no cookie HttpOnly, nunca no corpo. */
    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refresh(
            $request->cookie(config('mindcat.auth.refresh_cookie'))
        );

        return $this->respondWithTokens($result, 'Sessão renovada.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()
            ->json(['message' => 'Logout realizado com sucesso!'])
            ->withCookie($this->forgetRefreshCookie());
    }

    public function userProfile(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    private function respondWithTokens(array $result, string $message, int $status = 200): JsonResponse
    {
        $tokens = $result['tokens'];

        return response()->json([
            'message'    => $message,
            'user'       => $result['user'],
            'token'      => $tokens['access'],
            'expires_in' => $tokens['expires_in'],
        ], $status)->withCookie($this->refreshCookie($tokens['refresh']));
    }

    private function refreshCookie(string $value): Cookie
    {
        return cookie(
            config('mindcat.auth.refresh_cookie'),
            $value,
            (int) config('mindcat.auth.refresh_ttl_days') * 24 * 60,
            config('mindcat.auth.refresh_cookie_path'),
            config('mindcat.auth.refresh_cookie_domain'),
            (bool) config('mindcat.auth.refresh_cookie_secure'),
            true,
            false,
            'strict'
        );
    }

    private function forgetRefreshCookie(): Cookie
    {
        return cookie()->forget(
            config('mindcat.auth.refresh_cookie'),
            config('mindcat.auth.refresh_cookie_path'),
            config('mindcat.auth.refresh_cookie_domain')
        );
    }
}