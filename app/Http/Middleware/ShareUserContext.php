<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ShareUserContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            Log::shareContext([
                'user_id' => $user->id,
                'role'    => $user->role,
            ]);
        }

        return $next($request);
    }
}