<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProfileService
{
    public function getOrCreate(User $user): UserProfile
    {
        return $user->profile()->firstOrCreate([]);
    }

    public function update(User $user, array $data): UserProfile
    {
        $profile = $this->getOrCreate($user);
        $profile->fill($data)->save();

        return $profile;
    }

    public function setDiaryPassword(User $user, ?string $currentPassword, string $newPassword): void
    {
        $profile = $this->getOrCreate($user);

        if ($profile->diary_password_hash) {
            if (!$currentPassword || !Hash::check($currentPassword, $profile->diary_password_hash)) {
                throw new HttpException(403, 'Senha atual inválida.');
            }
        }

        $profile->diary_password_hash = Hash::make($newPassword);
        $profile->save();
    }
}
