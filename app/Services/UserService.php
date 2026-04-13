<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getProfile(User $user): User
    {
        $user->load('profile');

        if ($user->profile) {
            $user->profile->makeHidden('diary_password_hash');
        }

        return $user;
    }

    public function update(User $user, array $data): User
    {
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $user;
    }

    public function destroy(User $user): void
    {
        $user->tokens()->delete();
        $user->delete();
    }
}
