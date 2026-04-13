<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {}

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->validated();

        $task = $this->taskService->store(
            $request->user(),
            $data['patient_id'],
            $data['title']
        );

        return response()->json($task, 201);
    }

    public function index(Request $request): JsonResponse
    {
        $scope = $request->query('scope', 'mine');

        $tasks = $this->taskService->index($request->user(), $scope);

        return response()->json($tasks);
    }

    public function markDone(Request $request, Task $task): JsonResponse
    {
        $task = $this->taskService->markDone($request->user(), $task);

        return response()->json($task);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->taskService->destroy($request->user(), $task);

        return response()->json([
            'message' => 'Tarefa removida.',
        ]);
    }
}
