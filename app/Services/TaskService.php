<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TaskService
{
    public function store(User $pro, int $patientId, string $title): Task
    {
        $patient = User::findOrFail($patientId);

        if (!Gate::forUser($pro)->allows('view-patient', $patient)) {
            throw new HttpException(403, 'Sem acesso ao paciente.');
        }

        return Task::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'title'      => $title,
            'status'     => 'active',
        ]);
    }

    public function index(User $user, string $scope)
    {
        if ($user->role === 'pro' && $scope === 'assigned') {
            return Task::where('pro_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate(30);
        }

        return Task::where('patient_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(30);
    }

    public function markDone(User $patient, Task $task): Task
    {
        if ($patient->id !== $task->patient_id) {
            throw new HttpException(403, 'Sem permissão.');
        }

        $task->update([
            'status'       => 'done',
            'completed_at' => now(),
        ]);

        return $task;
    }

    public function destroy(User $pro, Task $task): void
    {
        if ($pro->id !== $task->pro_id) {
            throw new HttpException(403, 'Sem permissão.');
        }

        $task->delete();
    }
}
