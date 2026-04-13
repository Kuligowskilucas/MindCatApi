<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\SetDiaryPasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $this->profileService->getOrCreate($request->user());

        return response()->json($profile);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $profile = $this->profileService->update(
            $request->user(),
            $request->validated()
        );

        return response()->json($profile);
    }

    public function setDiaryPassword(SetDiaryPasswordRequest $request): JsonResponse
    {
        $this->profileService->setDiaryPassword(
            $request->user(),
            $request->input('current_password'),
            $request->input('new_password')
        );

        return response()->json([
            'message' => 'Senha do diário atualizada com sucesso.',
        ]);
    }
}
