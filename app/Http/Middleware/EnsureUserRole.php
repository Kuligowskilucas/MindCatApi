<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


class EnsureUserRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)){
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return $next($request);
    }
}
