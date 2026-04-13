<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserMoodTracking;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PatientService
{
    public function summary(User $pro, int $patientId): array
    {
        $patient = User::findOrFail($patientId);

        if (!Gate::forUser($pro)->allows('view-patient', $patient)) {
            throw new HttpException(403, 'Sem permissão.');
        }

        return [
            'patient'             => ['id' => $patient->id, 'name' => $patient->name],
            'moods'               => UserMoodTracking::where('user_id', $patient->id)
                                        ->orderByDesc('recorded_at')
                                        ->limit(14)
                                        ->get(),
            'exercises_completed' => UserActivity::where('user_id', $patient->id)
                                        ->where('is_completed', 1)
                                        ->count(),
            'diary'               => $patient->diaryEntries()
                                        ->select('id', 'created_at')
                                        ->orderByDesc('created_at')
                                        ->limit(10)
                                        ->get(),
        ];
    }
}
