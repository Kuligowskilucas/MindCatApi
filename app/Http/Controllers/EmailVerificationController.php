<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        $user = User::find($id);

        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return response()->json([
                'message' => 'Link de confirmação inválido.',
                'code'    => 'invalid_verification_link',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message'          => 'E-mail já confirmado.',
                'already_verified' => true,
            ]);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json([
            'message' => 'E-mail confirmado com sucesso.',
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => 'Se houver uma conta pendente com esse e-mail, enviamos um novo link de confirmação.',
        ]);
    }
}