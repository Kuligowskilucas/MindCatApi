<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;


class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        try {
            $userRole = $user->role;
        } catch (\ValueError) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $allowed = array_filter(array_map(
            fn (string $role) => Role::tryFrom($role),
            $roles
        ));

        if (!$userRole instanceof Role || !in_array($userRole, $allowed, true)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return $next($request);
    }
}
