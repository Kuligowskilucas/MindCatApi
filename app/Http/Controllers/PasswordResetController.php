<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(
        private PasswordResetService $passwordResetService
    ) {}

    public function sendCode(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->sendCode($request->validated()['email']);

        return response()->json([
            'message' => 'Se o email estiver cadastrado, você receberá um código.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->passwordResetService->resetPassword(
            $data['email'],
            $data['code'],
            $data['password']
        );

        return response()->json([
            'message' => 'Senha redefinida com sucesso!',
        ]);
    }
}
