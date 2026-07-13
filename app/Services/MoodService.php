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
        $recordedAt = Carbon::parse($data['recorded_at'] ?? now());

        // O cliente manda recorded_at; sem isso dá pra forjar datas
        // e sujar o gráfico que o profissional lê.
        if ($recordedAt->isFuture()) {
            throw new HttpException(422, 'Não é possível registrar humor no futuro.');
        }

        if ($recordedAt->lessThan(now()->subDays(7))) {
            throw new HttpException(422, 'Só é possível registrar humor dos últimos 7 dias.');
        }

        $day = $recordedAt->copy()->startOfDay();

        $exists = UserMoodTracking::where('user_id', $user->id)
            ->whereBetween('recorded_at', [$day, $day->copy()->endOfDay()])
            ->exists();

        if ($exists) {
            throw new HttpException(409, 'O humor já foi registrado nesse dia.');
        }

        return UserMoodTracking::create([
            'user_id'          => $user->id,
            'mood_level'       => $data['mood_level'],
            'mood_description' => $data['mood_description'] ?? null,
            'recorded_at'      => $recordedAt,
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

        // Conteúdo do usuário: exclusão é definitiva.
        $row->forceDelete();
    }
}