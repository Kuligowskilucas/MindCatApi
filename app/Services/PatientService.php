<?php

namespace App\Services;

use App\Models\Feeling;
use App\Models\User;
use App\Models\Task;
use App\Models\UserMoodTracking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PatientService
{
    public function summary(User $pro, int $patientId, int $days = 30): array
    {
        $patient = User::findOrFail($patientId);

        if (!Gate::forUser($pro)->allows('view-patient', $patient)) {
            throw new HttpException(403, 'Sem permissão.');
        }

        $start = Carbon::now()->subDays($days - 1)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $feelingsFrequency = Feeling::withCount(['moods' => function ($query) use ($patient, $start, $end) {
                $query->where('user_mood_tracking.user_id', $patient->id)
                    ->whereBetween('user_mood_tracking.recorded_at', [$start, $end]);
            }])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($feeling) => $feeling->moods_count > 0)
            ->sortByDesc('moods_count')
            ->values()
            ->map(fn ($feeling) => [
                'slug'  => $feeling->slug,
                'label' => $feeling->label,
                'count' => $feeling->moods_count,
            ]);

        return [
            'patient'             => ['id' => $patient->id, 'name' => $patient->name],
            'range_days'          => $days,
            'moods'               => UserMoodTracking::where('user_id', $patient->id)
                                        ->whereBetween('recorded_at', [$start, $end])
                                        ->with('feelings')
                                        ->orderByDesc('recorded_at')
                                        ->get(),
            'feelings_frequency'  => $feelingsFrequency,
            'exercises_completed' => Task::where('patient_id', $patient->id)
                                        ->where('pro_id', $pro->id)
                                        ->where('status', 'done')
                                        ->count(),
        ];
    }
}
