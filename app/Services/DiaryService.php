<?php

namespace App\Services;

use App\Models\DiaryEntry;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            ->get();
    }

    public function destroy(User $user, int $entryId, string $diaryPassword): void
    {
        $this->verifyDiaryPassword($user, $diaryPassword);

        $entry = DiaryEntry::where('user_id', $user->id)->findOrFail($entryId);
        $entry->delete();
    }

    private function verifyDiaryPassword(User $user, string $password): void
    {
        $hash = optional($user->profile)->diary_password_hash;

        if (!$hash || !Hash::check($password, $hash)) {
            throw new HttpException(403, 'Senha do diário inválida.');
        }
    }
}
