<?php

namespace App\Services;

use App\Models\DiaryEntry;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class DiaryService
{
    public function store(User $user, string $content): DiaryEntry
    {
        return DiaryEntry::create([
            'user_id' => $user->id,
            'content' => $content,
        ]);
    }

    public function index(User $user, string $diaryPassword)
    {
        $this->verifyDiaryPassword($user, $diaryPassword);

        return DiaryEntry::where('user_id', $user->id)
            ->latest('created_at')
            ->get()
            ->map(fn (DiaryEntry $entry) => [
                'id'         => $entry->id,
                'user_id'    => $entry->user_id,
                'content'    => $this->safeContent($entry),
                'created_at' => $entry->created_at,
                'updated_at' => $entry->updated_at,
            ]);
    }

    public function destroy(User $user, int $entryId, string $diaryPassword): void
    {
        $this->verifyDiaryPassword($user, $diaryPassword);

        $entry = DiaryEntry::where('user_id', $user->id)->findOrFail($entryId);

        $entry->forceDelete();
    }

    private function verifyDiaryPassword(User $user, string $password): void
    {
        $hash = optional($user->profile)->diary_password_hash;

        if (!$hash || !Hash::check($password, $hash)) {
            throw new HttpException(403, 'Senha do diário inválida.');
        }
    }

    private function safeContent(DiaryEntry $entry): ?string
    {
        try {
            return $entry->content;
        } catch (DecryptException $e) {
            Log::warning('Entrada de diário indecifrável', ['id' => $entry->id]);

            return null;
        }
    }
}