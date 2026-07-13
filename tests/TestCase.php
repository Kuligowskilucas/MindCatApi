<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;


abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function giveDiaryPassword(User $user, string $password = 'Diario123'): UserProfile
    {
        $profile = $user->profile()->firstOrCreate([]);
        $profile->diary_password_hash = Hash::make($password);
        $profile->save();

        return $profile;
    }
}

