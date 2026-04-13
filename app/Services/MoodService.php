<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMoodTracking;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MoodService
{
    public function store(User $user, array $data): UserMoodTracking
    {
        $day = Carbon::parse($data['recorded_at'] ?? now())->startOfDay();

        $exists = UserMoodTracking::where('user_id', $user->id)
            ->whereBetween('recorded_at', [$day, (clone $day)->endOfDay()])
            ->exists();

        if ($exists) {
            throw new HttpException(409, 'O humor já foi registrado hoje.');
        }

        return UserMoodTracking::create([
            'user_id'          => $user->id,
            'mood_level'       => $data['mood_level'],
            'mood_description' => $data['mood_description'] ?? null,
            'recorded_at'      => $data['recorded_at'] ?? now(),
        ]);
    }

    public function index(User $user, ?string $from, ?string $to)
    {
        $query = UserMoodTracking::where('user_id', $user->id)
            ->orderByDesc('recorded_at');

        if ($from) {
            $query->where('recorded_at', '>=', $from);
        }

        if ($to) {
            $query->where('recorded_at', '<=', $to);
        }

        return $query->paginate(30);
    }

    public function destroy(User $user, int $id): void
    {
        $row = UserMoodTracking::where('user_id', $user->id)->findOrFail($id);
        $row->delete();
    }
}
