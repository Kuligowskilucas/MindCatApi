<?php

namespace App\Http\Controllers;

use App\Http\Requests\Diary\DiaryPasswordRequest;
use App\Http\Requests\Diary\StoreDiaryRequest;
use App\Services\DiaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiaryController extends Controller
{
    public function __construct(
        private DiaryService $diaryService
    ) {}

    public function store(StoreDiaryRequest $request): JsonResponse
    {
        $entry = $this->diaryService->store(
            $request->user(),
            $request->validated()['content']
        );

        return response()->json([
            'message' => 'Entrada criada com sucesso!',
            'entry'   => $entry,
        ], 201);
    }

    public function index(DiaryPasswordRequest $request): JsonResponse
    {
        $entries = $this->diaryService->index(
            $request->user(),
            $request->validated()['diary_password']
        );

        return response()->json($entries);
    }

    public function destroy(DiaryPasswordRequest $request, int $id): JsonResponse
    {
        $this->diaryService->destroy(
            $request->user(),
            $id,
            $request->validated()['diary_password']
        );

        return response()->json([
            'message' => 'Entrada deletada com sucesso!',
        ]);
    }
}
