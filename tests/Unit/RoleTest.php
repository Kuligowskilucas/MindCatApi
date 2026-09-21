<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function test_enum_values_match_persisted_strings(): void
    {
        $this->assertSame('patient', Role::Patient->value);
        $this->assertSame('pro', Role::Pro->value);
        $this->assertSame('admin', Role::Admin->value);
    }

    public function test_try_from_unknown_value_returns_null(): void
    {
        $this->assertNull(Role::tryFrom('superadmin'));
    }

    public function test_user_role_helpers_reflect_assigned_role(): void
    {
        $user = new User(['role' => 'patient']);
        $this->assertTrue($user->isPatient());
        $this->assertFalse($user->isPro());
        $this->assertFalse($user->isAdmin());

        $user = new User(['role' => 'pro']);
        $this->assertTrue($user->isPro());
        $this->assertFalse($user->isPatient());
        $this->assertFalse($user->isAdmin());

        $user = new User(['role' => 'admin']);
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isPatient());
        $this->assertFalse($user->isPro());
    }
}
