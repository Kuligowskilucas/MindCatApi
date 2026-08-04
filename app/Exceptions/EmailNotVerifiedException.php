<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class EmailNotVerifiedException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Confirme seu e-mail para acessar sua conta.',
            'code'    => 'email_not_verified',
        ], 403);
    }
}