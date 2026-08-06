<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();

        Log::shareContext([
            'request_id' => $requestId,
            'method'     => $request->method(),
            'path'       => '/' . ltrim($request->path(), '/'),
            'ip'         => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}