<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        // SPA em domínio stateful: já deixa a sessão autenticada.
        if ($request->hasSession()) {
            Auth::guard('web')->login($result['model']);
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Usuário registrado com sucesso',
                'user'    => $result['user'],
            ], 201);
        }

        // Cliente mobile: token Bearer.
        return response()->json([
            'message' => 'Usuário registrado com sucesso',
            'user'    => $result['user'],
            'token'   => $result['token'],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if ($request->hasSession()) {
            Auth::guard('web')->login($result['model']);
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Login realizado com sucesso!',
                'user'    => $result['user'],
            ]);
        }

        return response()->json([
            'message' => 'Login realizado com sucesso!',
            'user'    => $result['user'],
            'token'   => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } else {
            $this->authService->logout($request->user());
        }

        return response()->json([
            'message' => 'Logout realizado com sucesso!',
        ]);
    }

    public function userProfile(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}