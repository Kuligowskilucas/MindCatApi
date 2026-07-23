<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Impede que o refresh token seja usado como credencial comum.
 * Sem isto, o token de TTL longo guardado no cookie autenticaria qualquer
 * rota sob `auth:sanctum` — exatamente o que a troca por access curto evita.
 * Requisições sem token (sessão, testes com actingAs) passam intactas.
 */
class EnsureAccessToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token && $token->can(AuthService::ABILITY_REFRESH) && !$token->can(AuthService::ABILITY_ACCESS)) {
            return response()->json([
                'message' => 'Sua sessão expirou. Entre novamente.',
            ], 401);
        }

        return $next($request);
    }
}