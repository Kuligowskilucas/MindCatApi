<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;

class DataExportService
{
    public function __construct(
        private DiaryService $diary
    ) {}

    public function export(User $user, ?string $diaryPassword): array
    {
        $user->loadMissing('profile');
        $profile = $user->profile;

        return [
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'created_at' => optional($user->created_at)->toIso8601String(),
            ],
            'profile' => $profile ? [
                'treatment_type'                  => $profile->treatment_type,
                'use_ai'                          => $profile->use_ai,
                'tdah_reminder'                   => $profile->tdah_reminder,
                'push_notifications'              => $profile->push_notifications,
                'progress_bar'                    => $profile->progress_bar,
                'consent_share_with_professional' => $profile->consent_share_with_professional,
            ] : null,
            'moods' => $user->moods()
                ->with('feelings')
                ->orderBy('recorded_at')
                ->get()
                ->map(fn ($m) => [
                    'mood_level'  => $m->mood_level,
                    'thought'     => $m->thought,
                    'behavior'    => $m->behavior,
                    'feelings'    => $m->feelings->pluck('slug')->all(),
                    'recorded_at' => optional($m->recorded_at)->toIso8601String(),
                ])
                ->all(),
            'tasks' => Task::where('patient_id', $user->id)
                ->orderBy('created_at')
                ->get()
                ->map(fn ($t) => [
                    'title'        => $t->title,
                    'status'       => $t->status,
                    'completed_at' => optional($t->completed_at)->toIso8601String(),
                ])
                ->all(),
            'diary' => $this->diary->entriesForExport($user, $diaryPassword),
        ];
    }
}
