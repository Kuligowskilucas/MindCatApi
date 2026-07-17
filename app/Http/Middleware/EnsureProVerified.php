<?php

namespace App\Http\Middleware;

use App\Models\ProfessionalCredential;
use Closure;
use Illuminate\Http\Request;

/**
 * Libera a rota só se o usuário for um pro com credencial APROVADA.
 * Normalmente roda depois de `role:pro`, então aqui a checagem é da credencial.
 * O `code` no JSON deixa o front distinguir este 403 (credencial) de outros
 * (papel errado, consentimento) e redirecionar pra tela de verificação.
 */
class EnsureProVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $approved = $user?->credential?->status === ProfessionalCredential::STATUS_APPROVED;

        if (!$approved) {
            return response()->json([
                'message' => 'Sua credencial profissional ainda não foi aprovada.',
                'code'    => 'credential_not_approved',
            ], 403);
        }

        return $next($request);
    }
}