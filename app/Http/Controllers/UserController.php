<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function me(Request $request): JsonResponse
    {
        $user = $this->userService->getProfile($request->user());

        return response()->json($user);
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        $user = $this->userService->update(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Usuário atualizado com sucesso!',
            'user'    => $user,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->userService->destroy($request->user());

        return response()->json([
            'message' => 'Usuário deletado com sucesso!',
        ]);
    }
}
