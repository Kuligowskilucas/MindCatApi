<?php

namespace App\Http\Controllers;

use App\Http\Requests\Mood\IndexMoodRequest;
use App\Http\Requests\Mood\StoreMoodRequest;
use App\Services\MoodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MoodController extends Controller
{
    public function __construct(
        private MoodService $moodService
    ) {}

    public function store(StoreMoodRequest $request): JsonResponse
    {
        $mood = $this->moodService->store(
            $request->user(),
            $request->validated()
        );

        return response()->json($mood, 201);
    }

    public function index(IndexMoodRequest $request): JsonResponse
    {
        $moods = $this->moodService->index(
            $request->user(),
            $request->input('from'),
            $request->input('to')
        );

        return response()->json($moods);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->moodService->destroy($request->user(), $id);

        return response()->json([
            'message' => 'Registro removido.',
        ]);
    }
}
